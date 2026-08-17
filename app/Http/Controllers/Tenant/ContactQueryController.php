<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\Contact;
use App\Models\Central\DemoRequest;
use App\Support\PlatformOpsNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactQueryController extends Controller
{
    public function storeContactQuery(Request $request)
    {
        $validated = $request->validate(
            [
                'name'          => 'required|string|max:255',
                'company'       => 'nullable|string|max:255',
                'email'         => 'required|email:rfc,dns|max:255',
                'phone'         => 'nullable|string|max:30',
                'company_size'  => 'nullable|in:1-10,11-50,51-200,200+',
                'inquiry_type'  => 'required|in:demo,pricing,sales,partnership,support,general',
                'message'       => 'required|string|max:5000',
            ],
            [
                'name.required'         => 'Please enter your full name.',
                'email.required'        => 'Business email is required.',
                'email.email'           => 'Please enter a valid business email address.',
                'inquiry_type.required' => 'Please select an inquiry type.',
                'message.required'      => 'Please describe your inquiry.',
            ]
        );

        DB::beginTransaction();

        try {
            $message = $validated['message'];
            $attribution = \App\Support\MarketingAttribution::summaryLine();
            $landing = \App\Support\MarketingAttribution::landingPath();
            $metaBits = array_filter([
                $landing ? 'landing='.$landing : null,
                $attribution !== '' ? 'attr: '.$attribution : null,
            ]);
            if ($metaBits !== []) {
                $message = trim($message."\n\n".'[Marketing] '.implode(' · ', $metaBits));
            }

            Contact::create([
                'name'          => $validated['name'],
                'company'       => $validated['company'] ?? null,
                'email'         => $validated['email'],
                'phone'         => $validated['phone'] ?? null,
                'company_size'  => $validated['company_size'] ?? null,
                'inquiry_type'  => $validated['inquiry_type'],
                'message'       => $message,
                'status'        => 'new',
            ]);

            if ($validated['inquiry_type'] === 'demo') {
                DemoRequest::query()->updateOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name'        => $validated['name'],
                        'company'     => $validated['company'] ?? null,
                        'description' => $message,
                        'status'      => 'pending',
                    ]
                );
            }

            DB::commit();

            PlatformOpsNotifier::alert(
                'contact_query',
                'New contact inquiry from ' . $validated['name'],
                [
                    'name'         => $validated['name'],
                    'email'        => $validated['email'],
                    'inquiry_type' => $validated['inquiry_type'],
                    'url'          => route('super-admin.contact-queries.get'),
                ]
            );

            return back()->with(
                'success',
                'Thank you for contacting Ledrix! Our team has received your inquiry and will get back to you within 24 hours.'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Contact Form Error', [
                'email'   => $validated['email'] ?? null,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'We were unable to submit your inquiry at the moment. Please try again later.'
                );
        }
    }

    public function superContactQuries()
    {
        $queries = Contact::paginate(15);

        return view('central.pages.contact-queries', compact('queries'));
    }

    public function updateContactStatus(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|exists:central.contacts,id',
            'status'     => 'required|in:new,contacted,in_progress,replied,closed',
        ]);

        DB::beginTransaction();

        try {
            $contact = Contact::findOrFail($request->contact_id);

            $contact->status = $request->status;

            switch ($request->status) {
                case 'contacted':
                    $contact->last_contacted_at = now();
                    break;

                case 'replied':
                    $contact->replied_at = now();
                    break;
            }

            $contact->save();

            DB::commit();

            return back()->with(
                'success',
                'Contact inquiry status updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Contact Status Update Error', [
                'contact_id' => $request->contact_id,
                'status'     => $request->status,
                'message'    => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Unable to update contact status'
            );
        }
    }
}
