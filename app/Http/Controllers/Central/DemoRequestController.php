<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\DemoRequest;
use App\Models\Central\Tenant;
use App\Support\MarketingAttribution;
use App\Support\PlatformOpsNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemoRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DemoRequest::query()
            ->with('tenant:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $demos = $query->paginate(20)->withQueryString();
        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);

        return view('central.pages.demo-requests', compact('demos', 'tenants'));
    }

    public function update(Request $request, int $id)
    {
        $demo = DemoRequest::query()->findOrFail($id);

        $validated = $request->validate([
            'status'          => ['required', Rule::in(['pending', 'contacted', 'demo_sent', 'active', 'inactive'])],
            'tenant_id'       => ['nullable', 'integer', Rule::exists(Tenant::class, 'id')],
            'demo_expires_at' => ['nullable', 'date'],
            'admin_note'      => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'status'    => $validated['status'],
            'tenant_id' => $validated['tenant_id'] ?? null,
        ];

        if ($validated['status'] === 'demo_sent' && ! $demo->demo_sent_at) {
            $payload['demo_sent_at'] = now();
        }

        if (! empty($validated['demo_expires_at'])) {
            $payload['demo_expires_at'] = $validated['demo_expires_at'];
        }

        if (! empty($validated['admin_note'])) {
            $desc = trim((string) $demo->description);
            $note = '[Admin] ' . $validated['admin_note'];
            $payload['description'] = $desc === '' ? $note : $desc . "\n\n" . $note;
        }

        $demo->update($payload);

        return back()->with('success', 'Demo request updated.');
    }

    /** Public marketing form submission. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'company'     => ['nullable', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'source'      => ['nullable', 'string', 'max:64'],
        ]);

        $attribution = MarketingAttribution::summaryLine();
        $notes = trim((string) ($validated['description'] ?? ''));
        $source = isset($validated['source']) ? (string) $validated['source'] : '';
        $landing = MarketingAttribution::landingPath();
        $metaBits = array_filter([
            $source !== '' ? 'source='.$source : null,
            $landing ? 'landing='.$landing : null,
            $attribution !== '' ? 'attr: '.$attribution : null,
        ]);
        if ($metaBits !== []) {
            $notes = trim($notes.($notes !== '' ? "\n\n" : '').'[Marketing] '.implode(' · ', $metaBits));
        }

        $existing = DemoRequest::query()->where('email', $validated['email'])->first();

        if ($existing) {
            // Keep admin workflow status; only refresh contact details / notes.
            $existing->update([
                'name'        => $validated['name'],
                'company'     => $validated['company'] ?? $existing->company,
                'description' => $notes !== '' ? $notes : $existing->description,
            ]);
        } else {
            DemoRequest::query()->create([
                'name'        => $validated['name'],
                'company'     => $validated['company'] ?? null,
                'description' => $notes !== '' ? $notes : null,
                'email'       => $validated['email'],
                'status'      => 'pending',
            ]);
        }

        PlatformOpsNotifier::alert(
            'demo_request',
            'New demo request from ' . $validated['name'],
            [
                'name'    => $validated['name'],
                'email'   => $validated['email'],
                'company' => $validated['company'] ?? '—',
                'url'     => route('super-admin.demo-requests.get'),
            ]
        );

        return redirect()
            ->route('lp.demo.thanks')
            ->with('success', 'Thanks! Your demo request was received. Our team will contact you shortly.');
    }
}
