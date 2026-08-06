<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Mail\PlatformSupportReplyMail;
use App\Models\Central\PlatformSupportReply;
use App\Models\Central\PlatformSupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class PlatformSupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformSupportTicket::query()
            ->with(['tenant:id,name,email', 'assignedTo:id,name', 'latestReply'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->open();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->boolean('unassigned')) {
            $query->unassigned();
        }

        if ($request->boolean('urgent')) {
            $query->urgent();
        }

        $tickets = $query->paginate(20)->withQueryString();

        return view('central.pages.support-tickets', compact('tickets'));
    }

    public function show(int $id)
    {
        $ticket = PlatformSupportTicket::query()
            ->with([
                'tenant:id,name,email,slug',
                'assignedTo:id,name',
                'replies' => fn ($q) => $q->orderBy('created_at'),
            ])
            ->findOrFail($id);

        return view('central.pages.support-ticket-detail', compact('ticket'));
    }

    public function reply(Request $request, int $id)
    {
        $ticket = PlatformSupportTicket::query()->findOrFail($id);
        $admin = Auth::guard('super_admin')->user();

        $validated = $request->validate([
            'message'     => ['required', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $isInternal = $request->boolean('is_internal');

        PlatformSupportReply::query()->create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'super_admin',
            'sender_id'   => $admin->id,
            'message'     => $validated['message'],
            'is_internal' => $isInternal,
        ]);

        $updates = [];
        if (! $isInternal && ! $ticket->first_replied_at) {
            $updates['first_replied_at'] = now();
        }
        if ($ticket->status === 'open') {
            $updates['status'] = 'in_progress';
            $updates['assigned_to'] = $ticket->assigned_to ?: $admin->id;
        }
        if ($updates !== []) {
            $ticket->update($updates);
        } else {
            $ticket->touch();
        }

        if (! $isInternal) {
            $ticket->loadMissing('tenant:id,email,name');
            if ($ticket->tenant?->email) {
                try {
                    Mail::to($ticket->tenant->email)->send(
                        new PlatformSupportReplyMail($ticket, $validated['message'])
                    );
                } catch (Throwable $e) {
                    Log::warning('Support reply mail failed', [
                        'ticket_id' => $ticket->id,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }
        }

        return back()->with('success', $isInternal ? 'Internal note added.' : 'Reply sent to tenant.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $ticket = PlatformSupportTicket::query()->findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['assign', 'resolve', 'close', 'hold', 'reopen'])],
        ]);

        $admin = Auth::guard('super_admin')->user();

        match ($validated['action']) {
            'assign'  => $ticket->assign((int) $admin->id),
            'resolve' => $ticket->resolve(),
            'close'   => $ticket->close(),
            'hold'    => $ticket->hold(),
            'reopen'  => $ticket->update([
                'status'      => 'open',
                'resolved_at' => null,
                'closed_at'   => null,
            ]),
        };

        return back()->with('success', 'Ticket updated.');
    }
}
