<?php

namespace App\Http\Controllers\upwork;

use App\Http\Controllers\Controller;
use App\Models\Upwork\UpworkClient;
use App\Models\Upwork\UpworkOrder;
use App\Models\Upwork\UpworkPaymentLink;
use Illuminate\Http\Request;

class CrudController extends Controller
{
    public function deleteClient(Request $request)
    {
        $client = UpworkClient::find($request->user_id);
        // dd($client, $request->all());
        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found']);
        }
        $client->delete();
        return response()->json(['success' => true]);
    }

    public function updateClientStatus(Request $request)
    {
        $client = UpworkClient::find($request->user_id); // or client model if separate

        if (!$client) {
            return response()->json(['success' => false]);
        }

        $client->status = $request->status; // active / inactive
        $client->save();

        return response()->json(['success' => true]);
    }

    public function clientAccountAccess(Request $request)
    {
        // Validate the input
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'password'  => 'required|string|min:6|max:12',
        ]);
        // Retrieve the client by ID
        $client = UpworkClient::findOrFail($request->client_id);
        $client->password = $request->password;
        $plainPassword = $request->password;
        $client->save();
        // dd($client);

        // Create the login URL (you can change this if needed)
        $loginUrl = route('client.login.get');
        // Ensure the email is valid
        if (!filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'Invalid email address.');
        }
        // Send the notification with a 5-second delay
        // try {
        //     Notification::route('mail', $client->email)
        //         ->notify(
        //             (new SendClientAccountCRMLink($client, $plainPassword, $loginUrl))
        //                 ->delay(now()->addSeconds(5))
        //         );
        // } catch (\Exception $e) {
        //     return back()->with('error', 'Failed to send the email. Error: ' . $e->getMessage());
        // }

        // Return a success message
        return back()->with('success', 'Client account password updated successfully.');
    }

    public function changePaylinkStatus(Request $request)
    {
        $request->validate([
            'id'             => ['required', 'integer', 'exists:payment_links,id'],
            'is_active_link' => ['required', 'in:true,false,1,0'],
        ]);
        $link = UpworkPaymentLink::findOrFail($request->id);
        // Convert string → boolean
        $isActive = filter_var($request->is_active_link, FILTER_VALIDATE_BOOLEAN);
        $link->is_active_link = $isActive;
        $link->save();
        return response()->json([
            'success' => true,
            'message' => "Payment link updated successfully.",
            'active'  => $isActive
        ]);
    }

    public function deleteOrder($id)
    {
        $order = UpworkOrder::find($id);
        if (!$order) {
            return redirect()->back()->with('error', 'Order not found');
        }
        $order->delete();
        return redirect()->back()->with('success', 'Order Deleted Successfully!');
    }
}
