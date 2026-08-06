<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\PlatformWebhookEvent;
use Illuminate\Http\Request;

class WebhookEventController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformWebhookEvent::query()
            ->with('tenant:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $events = $query->paginate(30)->withQueryString();

        return view('central.pages.webhook-events', compact('events'));
    }

    public function show(int $id)
    {
        $event = PlatformWebhookEvent::query()
            ->with('tenant:id,name,email')
            ->findOrFail($id);

        return view('central.pages.webhook-event-detail', compact('event'));
    }

    public function retry(int $id)
    {
        $event = PlatformWebhookEvent::query()->findOrFail($id);

        if (! $event->canRetry()) {
            return back()->with('error', 'This event cannot be retried.');
        }

        // Reset to pending so ops can re-trigger the provider callback / inspect payload.
        $event->update([
            'status'        => 'pending',
            'error_message' => null,
        ]);

        return back()->with(
            'success',
            'Event marked pending. Re-deliver from the provider or process manually from the payload.'
        );
    }
}
