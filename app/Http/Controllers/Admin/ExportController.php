<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ClientTicket;
use App\Support\PortalAuthorization;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function exportLeadsCsv(Request $request, string $table): StreamedResponse
    {
        PortalAuthorization::requireAdmin();

        if (! Schema::hasTable($table)) {
            abort(404, 'Table not found.');
        }

        $query = DB::table($table);
        $tenantId = TenantContext::require();

        if (Schema::hasColumn($table, 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        $rowCount = (clone $query)->count();
        if ($rowCount < 300) {
            abort(403, "CSV export is only allowed for tables with 300 or more rows. Found: $rowCount");
        }

        $columns = $request->query('columns');
        if ($columns) {
            $columns = explode(',', $columns); // e.g. ?columns=id,name,email
            $columns = array_filter($columns, function ($col) use ($table) {
                return Schema::hasColumn($table, trim($col));
            });
        } else {
            $columns = Schema::getColumnListing($table);
        }

        $filename = $table . '_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($query, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            (clone $query)->select($columns)->orderBy('id')->chunk(1000, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, (array) $row);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

    public function adminOrderTickets(Order $order)
    {
        PortalAuthorization::requirePortalActor();
        PortalAuthorization::authorizeOrder($order);
        $order->load(['brand', 'client', 'tickets']);  // eager load tickets relation
        $tickets = $order->tickets()->latest()->paginate(10);
        // dd($order,$tickets);
        return view('admin.pages.order-tickets', compact('order', 'tickets'));
    }

    public function getTicketDetails($id)
    {
        $ticket = ClientTicket::with(['client', 'order'])->findOrFail($id);

        PortalAuthorization::requirePortalActor();
        PortalAuthorization::authorizeTicket($ticket);

        return response()->json([
            'id' => $ticket->id,
            'subject' => $ticket->subject ?? '—',
            'status' => $ticket->status,
            'description' => $ticket->description ?? '—',
            'client' => $ticket->client?->name ?? '—',
            'client_email' => $ticket->client?->email ?? '—',
            'order' => $ticket->order?->service_name ?? '—',
            'created_at' => $ticket->created_at->format('d M Y, h:i A'),
            'updated_at' => $ticket->updated_at->format('d M Y, h:i A'),
        ]);
    }


    public function updateTicketStatus(Request $request)
    {
        PortalAuthorization::requirePortalActor();

        $request->validate([
            'ticket_id' => 'required|integer|exists:client_tickets,id',
            'status'    => 'required|string|max:50',
        ]);

        $ticket = ClientTicket::findOrFail($request->ticket_id);
        PortalAuthorization::authorizeTicket($ticket);

        $ticket->status = $request->status;
        $ticket->save();

        return back()->with('success', 'Ticket status updated.');
    }

    public function deleteTickets(Request $request, $id = null)
    {
        PortalAuthorization::requireAdmin();
        $status = $request->status;
        // dd($request->all(), $status,$id);
        // Delete by ID (single lead)
        if ($id) {
            $ticket = ClientTicket::find($id);
            if (!$ticket) {
                return back()->with('error', "No ticket found with ID {$id}.");
            }
            PortalAuthorization::authorizeTicket($ticket);
            $ticket->delete();
            return back()->with('success', "Ticket with ID {$id} deleted successfully.");
        }
        // If neither ID nor Status provided
        // return response()->json([
        //     'status'  => false,
        //     'message' => 'Please provide either a lead ID or a status.'
        // ], 400);
        return back()->with('info', "Please provide either a lead ID or a status.");
    }
}
