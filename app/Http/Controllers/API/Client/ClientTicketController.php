<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\Order;
use App\Models\ClientTicket;
use App\Models\Questionnair;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClientTicketController extends Controller
{
    public function index(Request $request)
    {
        $client = auth('sanctum')->user();
        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Eager load brand, seller, order
        $tickets = ClientTicket::with(['brand', 'seller', 'order'])
            ->where('client_id', $client->id)
            ->latest()
            ->paginate(10);

        // Transform for return
        $ticketsTransformed = $tickets->through(function ($ticket) {
            return [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'attachment' => $ticket->attachment,
                'brand' => [
                    'brand_name' => $ticket->brand?->brand_name,
                    'brand_url' => $ticket->brand?->brand_url,
                ],
                'seller' => [
                    'id' => $ticket->seller?->id,
                    'name' => $ticket->seller?->name,
                    'email' => $ticket->seller?->email,
                ],
                'order' => [
                    'id' => $ticket->order?->id,
                    'service_name' => $ticket->order?->service_name,
                ],
                'created_at' => $ticket->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'data' => $ticketsTransformed,
                'links' => $tickets->links(),  // for pagination links if needed
            ],
        ]);
    }

    public function store(Request $request)
    {
        $client = auth('sanctum')->user();
        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $order = Order::find($data['order_id']);
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        $ticket = new ClientTicket($data);
        $ticket->client_id = $client->id;
        $ticket->brand_id = $order->brand_id;
        $ticket->seller_id = $order->seller_id;
        $ticket->status = 'open';
        $ticket->source = 'api';

        // file upload
        if ($request->hasFile('attachments')) {
            $file = $request->file('attachments');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/attachments/'), $filename);
            $ticket->attachment = $filename;
        }

        $ticket->save();

        return response()->json([
            'status' => true,
            'message' => 'Ticket created successfully!',
            'ticket' => $ticket,
        ]);
    }

    public function ticketsByOrder(Request $request, Order $order)
    {
        $clientId = $request->user();
        if ($order->client_id !== $clientId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tickets = $order->tickets()->latest()->get();
        return response()->json($tickets);
    }

    public function destroy($id)
    {
        $ticket = ClientTicket::findOrFail($id);
        if ($ticket->client_id !== auth('sanctum')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted successfully.']);
    }


}
