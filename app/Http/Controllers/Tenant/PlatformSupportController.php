<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\PlatformSupportReply;
use App\Models\Central\PlatformSupportTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformSupportController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index()
    {
        $tenant = $this->organizationTenant();

        $tickets = PlatformSupportTicket::query()
            ->forTenant((int) $tenant->id)
            ->with('latestReply')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return $this->organizationView('support-index', compact('tenant', 'tickets'));
    }

    public function create()
    {
        $tenant = $this->organizationTenant();

        return $this->organizationView('support-create', compact('tenant'));
    }

    public function store(Request $request)
    {
        $tenant = $this->organizationTenant();

        $validated = $request->validate([
            'subject'     => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category'    => ['required', Rule::in(['billing', 'technical', 'feature_request', 'account', 'other'])],
            'priority'    => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
        ]);

        $ticket = PlatformSupportTicket::query()->create([
            'tenant_id'   => $tenant->id,
            'subject'     => $validated['subject'],
            'description' => $validated['description'],
            'category'    => $validated['category'],
            'priority'    => $validated['priority'],
            'status'      => 'open',
        ]);

        return $this->organizationRedirect(
            'support.show',
            $ticket->id,
            'success',
            'Support ticket created. Our team will reply soon.'
        );
    }

    public function show(int $id)
    {
        $tenant = $this->organizationTenant();

        $ticket = PlatformSupportTicket::query()
            ->forTenant((int) $tenant->id)
            ->with(['publicReplies' => fn ($q) => $q->orderBy('created_at')])
            ->findOrFail($id);

        return $this->organizationView('support-show', compact('tenant', 'ticket'));
    }

    public function reply(Request $request, int $id)
    {
        $tenant = $this->organizationTenant();

        $ticket = PlatformSupportTicket::query()
            ->forTenant((int) $tenant->id)
            ->findOrFail($id);

        abort_if($ticket->status === 'closed', 403, 'This ticket is closed.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Replies from CRM admin still represent the tenant org in the platform inbox.
        PlatformSupportReply::query()->create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'tenant',
            'sender_id'   => $tenant->id,
            'message'     => $validated['message'],
            'is_internal' => false,
        ]);

        if (in_array($ticket->status, ['resolved', 'on_hold'], true)) {
            $ticket->update(['status' => 'open', 'resolved_at' => null]);
        } else {
            $ticket->touch();
        }

        return back()->with('success', 'Reply sent.');
    }
}
