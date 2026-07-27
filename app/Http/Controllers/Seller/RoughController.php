<?php

namespace App\Http\Controllers\Seller;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LeadAssignment;
use App\Models\Seller;

class RoughController extends Controller
{
    public function updateAssignedLeadStatus(Request $request)
    {
        $validated = $request->validate([
            'assignment_id' => 'required|exists:lead_assignments,id',
            // 'status'        => 'required|in:accepted,rejected,in_progress,completed',
            'status' => 'required|in:assigned,in_progress,on_hold,completed,refund_requested,chargeback,rejected_by_client,cancelled',
        ]);

        $seller = auth('seller')->user();

        if (!$seller) {
            return back()->with('error', 'Only sellers can update assigned leads.');
        }

        // find assignment
        $assignment = LeadAssignment::where('id', $validated['assignment_id'])
            ->where('assigned_to', $seller->id) // make sure it's THEIR assignment
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not authorized to update this assignment.');
        }

        $assignment->status = $validated['status'];
        $assignment->save();

        return back()->with('success', 'Assignment status updated successfully.');
    }

    public function sellerUpdateClientStatus(Request $request)
    {
        $client = Client::find($request->user_id);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found']);
        }

        $client->status = $request->status; // "Active" / "Inactive"
        $client->save();

        return response()->json([
            'success' => true,
            'status' => $client->status
        ]);
    }

    public function adminUpdateSellerStatus(Request $request)
    {
        $lead = Seller::find($request->lead_id);
        // dd($lead);
        $lead->status = $request->status;
        $lead->save();
        return back()->with('success', 'Seller status updated.');
    }
}
