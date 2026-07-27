<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Client;
use App\Models\PaymentLink;
use App\Models\ClientTicket;
use Illuminate\Http\Request;
use App\Models\LeadAssignment;
use App\Http\Controllers\Controller;
use App\Support\PortalAuthorization;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SendClientAccountCRMLink;

class ManagementController extends Controller
{
    public function clientBriefs($id)
    {
        PortalAuthorization::requirePortalActor();

        $client = Client::findOrFail($id);
        PortalAuthorization::authorizeClient($client);

        $orders = Order::with([
            'brand:id,brand_name',
            'seller:id,name',
            'client:id,name,email',
            'brief',
        ])
            ->where('client_id', $client->id)
            ->where('order_type', 'original')
            ->when(auth('seller')->check() && ! auth('admin')->check(), function ($q) {
                $seller = auth('seller')->user();
                $role = $seller->role ?? $seller->is_seller;

                if ($role === 'front_seller') {
                    $q->where('brand_id', $seller->brand_id);
                } else {
                    $q->where(function ($w) use ($seller) {
                        $w->where('seller_id', $seller->id)
                            ->orWhere('owner_seller_id', $seller->id);
                    });
                }
            })
            ->latest('id')
            ->get();

        // ✅ Seller portal only — admins supervise tenants, sellers handle client briefs
        abort_if(auth('admin')->check() && ! auth('seller')->check(), 403, 'Briefs are managed by sellers and clients.');

        return view('sellers.pages.client-brief-forms', compact('orders', 'client'));
    }

    public function deleteDomain(Request $request)
    {
        PortalAuthorization::requireAdmin();
        $brand = Brand::find($request->domain_id);
        // dd($client, $request->all());
        if (!$brand) {
            return response()->json(['success' => false, 'message' => 'Domain not found']);
        }
        $brand->delete();
        return response()->json(['success' => true]);
    }

    public function deleteLeads(Request $request, $id = null)
    {
        PortalAuthorization::requireAdmin();
        $status = $request->status;
        // dd($request->all(), $status,$id);
        // Delete by ID (single lead)
        if ($id) {
            $lead = Lead::find($id);

            if (!$lead) {
                return back()->with('error', "No lead found with ID {$id}.");
            }
            // Delete related assignments first
            LeadAssignment::where('lead_id', $lead->id)->delete();
            // Delete the lead (cascade handles orders, payments, etc.)
            $lead->delete();
            return back()->with('success', "Lead with ID {$id} and related data deleted successfully.");
        }
        // Bulk delete by status is disabled in production — delete leads individually by ID.
        if ($status) {
            return back()->with('error', 'Bulk delete by status is disabled. Delete leads one at a time by ID.');
        }
        // If neither ID nor Status provided
        // return response()->json([
        //     'status'  => false,
        //     'message' => 'Please provide either a lead ID or a status.'
        // ], 400);
        return back()->with('info', "Please provide either a lead ID or a status.");
    }

    public function updateDomainStatus(Request $request)
    {
        PortalAuthorization::requireAdmin();
        $domain = Brand::find($request->domain_id); // or client model if separate
        if (!$domain) {
            return response()->json(['success' => false]);
        }
        $domain->status = $request->status; // active / penidng
        $domain->save();

        return response()->json(['success' => true]);
    }

    public function deleteClient(Request $request)
    {
        PortalAuthorization::requireAdmin();
        $client = Client::find($request->user_id);
        // dd($client, $request->all());
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found']);
        }
        $client->delete();
        return response()->json(['success' => true]);
    }

    public function updateClientStatus(Request $request)
    {
        PortalAuthorization::requirePortalActor();

        $client = Client::find($request->user_id);

        if (! $client) {
            return response()->json(['success' => false]);
        }

        PortalAuthorization::authorizeClient($client);

        $client->status = $request->status; // active / inactive
        $client->save();

        return response()->json(['success' => true]);
    }

    public function updateLeadStatus(Request $request)
    {
        PortalAuthorization::requirePortalActor();

        $request->validate([
            'lead_id' => 'required|integer|exists:leads,id',
            'status'  => 'required|string|max:50',
        ]);

        $lead = Lead::findOrFail($request->lead_id);
        PortalAuthorization::authorizeLead($lead);

        $lead->status = $request->status;
        $lead->save();

        return back()->with('success', 'Lead status updated.');
    }

    public function clientAccountAccess(Request $request)
    {
        PortalAuthorization::requirePortalActor();

        if (auth('seller')->check() && ! auth('admin')->check()) {
            app(TenantFeatureService::class)->assertEnabled('client_portal');
        }

        // Validate the input
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'password'  => 'required|string|min:6|max:12',
        ]);
        // Retrieve the client by ID
        $client = Client::findOrFail($request->client_id);
        PortalAuthorization::authorizeClient($client);

        $plainPassword = $request->password;
        $client->password = $plainPassword;

        $meta = is_array($client->meta) ? $client->meta : (json_decode($client->meta ?? '{}', true) ?: []);
        unset($meta['plain_password']);
        $meta['portal_access'] = true;
        $client->meta = $meta;
        $client->save();
        // dd($client);

        // Create the login URL (you can change this if needed)
        $loginUrl = route('client.login.get');
        // Ensure the email is valid
        if (!filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Invalid email address.');
        }
        // Send the notification with a 5-second delay
        try {
            Notification::route('mail', $client->email)
                ->notify(
                    (new SendClientAccountCRMLink($client, $plainPassword, $loginUrl))
                        ->delay(now()->addSeconds(5))
                );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send the email. Please try again later.');
        }

        // Return a success message
        return back()->with('success', 'Client account password updated successfully.');
    }

    public function changePaylinkStatus(Request $request)
    {
        PortalAuthorization::requirePortalActor();

        $request->validate([
            'id'             => ['required', 'integer', 'exists:payment_links,id'],
            'is_active_link' => ['required', 'in:true,false,1,0'],
        ]);

        $link = PaymentLink::withoutGlobalScopes()->findOrFail($request->id);

        $this->authorizePaylinkToggle($link);

        $isActive = filter_var($request->is_active_link, FILTER_VALIDATE_BOOLEAN);
        $link->is_active_link = $isActive;
        $link->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment link updated successfully.',
            'active'  => $isActive,
        ]);
    }

    private function authorizePaylinkToggle(PaymentLink $link): void
    {
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();
            abort_if(
                ($admin->role ?? null) === 'finance',
                403,
                'Finance accounts cannot perform this action.'
            );

            return;
        }

        $seller = auth('seller')->user();
        abort_unless($seller, 403, 'Unauthorized.');

        abort_unless(
            (int) $seller->brand_id === (int) $link->brand_id,
            403,
            'You cannot modify payment links for another brand.'
        );

        $role = $seller->role ?? $seller->is_seller;

        if ($role !== 'front_seller') {
            abort_unless(
                (int) $seller->id === (int) $link->seller_id
                    || (int) $seller->id === (int) $link->owner_seller_id,
                403,
                'You cannot modify this payment link.'
            );
        }
    }

    public function generateInvoice(Request $request, ?Order $order = null)
    {
        abort_unless($order, 404);

        PortalAuthorization::requirePortalActor();
        PortalAuthorization::authorizeOrder($order);

        $order->load([
            'brand:id,brand_name,brand_url',
            'client:id,name,email,phone',
            'seller:id,name,email,sudo_name',
            'lead:id,name,email,phone,domain_url,service',
            'payments:id,order_id,amount,currency,status,provider,created_at',
            'paymentLinks' => fn ($q) => $q->orderByDesc('last_issued_at')->orderByDesc('id'),
        ]);

        $client = $order->client;

        $latestActiveLink = $order->paymentLinks
            ->where('is_active_link', true)
            ->filter(function ($link) {
                return $link->expires_at === null || $link->expires_at->isFuture();
            })
            ->sortByDesc(fn ($link) => $link->last_issued_at ?? $link->created_at)
            ->first();

        $lastIssuedLink = $order->paymentLinks->first();

        $view = auth('admin')->check()
            ? 'admin.pages.invoice'
            : 'sellers.pages.invoice';

        return view($view, compact(
            'order',
            'client',
            'latestActiveLink',
            'lastIssuedLink'
        ));
    }

    public function toggleSettingDown()
    {
        Artisan::call('down', [
            '--render' => 'errors.503',
        ]);
        $status = 'maintainance mode';

        return redirect()->back()->with('status', "Site is now {$status}.");
    }

    public function toggleSettingUp()
    {
        Artisan::call('up');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('optimize:clear');
        return redirect()->route('welcome.get')->with('status', 'Site is now live again!');
    }
}
