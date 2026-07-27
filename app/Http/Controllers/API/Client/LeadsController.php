<?php

namespace App\Http\Controllers\API\Client;

use App\Models\Order;
use App\Models\Client;
use App\Models\ClientTicket;
use Illuminate\Http\Request;
use App\Services\LeadIntakeService;
use App\Services\ProjectNotify;
use App\Http\Controllers\Controller;
use App\Support\ClientPortalAuthorization;

class LeadsController extends Controller
{
    public function storeLead(Request $req, LeadIntakeService $intake)
    {
        $result = $intake->storeFromCrmPost($req);

        if ($result['duplicate']) {
            return response()->json([
                'ok'        => true,
                'duplicate' => true,
                'lead_id'   => $result['lead']->id,
            ], 200);
        }

        return response()->json([
            'ok'      => true,
            'lead_id' => $result['lead']->id,
        ], 201);
    }

    public function clientTickets()
    {
        $client = ClientPortalAuthorization::client();

        $tickets = ClientTicket::query()
            ->where('client_id', $client->id)
            ->latest('id')
            ->paginate(12);

        return view('clients.pages.tickets', compact('tickets'));
    }

    public function clientRaiseTicket(Order $order)
    {
        ClientPortalAuthorization::assertOwnsOrder($order);

        return view('clients.pages.raise-ticket', compact('order'));
    }

    public function clientTicketStore(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'order_id' => 'required|exists:orders,id',
        ]);

        $client = ClientPortalAuthorization::client();
        $order = ClientPortalAuthorization::orderForClient((int) $data['order_id']);

        $ticket = new ClientTicket();
        $ticket->client_id = $client->id;
        $ticket->brand_id = $order->brand_id;
        $ticket->seller_id = $order->seller_id;
        $ticket->order_id = $order->id;
        $ticket->subject = $data['subject'];
        $ticket->description = $data['description'];
        $ticket->priority = $data['priority'];
        $ticket->status = 'open';
        $ticket->source = 'crm';

        $file = $request->file('attachment')
            ?? $request->file('attachments.0')
            ?? (is_array($request->file('attachments')) ? ($request->file('attachments')[0] ?? null) : null);

        if ($file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/attachments/'), $fileName);
            $ticket->attachment = $fileName;
        }

        $ticket->save();

        ProjectNotify::created($ticket);

        return back()->with('success', 'Ticket created successfully!');
    }
}
