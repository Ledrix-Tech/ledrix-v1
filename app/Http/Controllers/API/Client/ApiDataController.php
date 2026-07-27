<?php

namespace App\Http\Controllers\API\Client;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\ProfileDetail;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class ApiDataController extends Controller
{

    public function clientBriefs(Request $request)
    {
        $client = $request->user(); // comes from Sanctum token

        $orders = Order::query()
            ->with(['brand:id,brand_name', 'seller:id,name', 'client:id,name,email'])
            ->where('client_id', $client->id)
            ->where('order_type', 'original')
            ->paginate(20);

        return response()->json([
            'status'  => true,
            'message' => 'Client briefs fetched successfully',
            'data'    => $orders,
        ]);
    }

    public function clientInvoices(Request $request)
    {
        $client = $request->user(); // From Sanctum

        $orders = Order::query()
            ->with(['brand:id,brand_name', 'seller:id,name', 'client:id,name,email'])
            ->where('client_id', $client->id)
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'status'  => true,
            'message' => 'Client invoices fetched successfully',
            'data'    => $orders,
        ]);
    }

    public function clientInvoiceDetails(Request $request, Order $order)
    {
        $client = $request->user(); // From Sanctum

        // Guard: prevent seeing someone else's order
        abort_unless($order->client_id === $client->id, 403);

        // Eager-load relationships
        $order->load([
            'brand:id,brand_name',
            'seller:id,name',
            'client:id,name,email',
            'paymentLinks:id,order_id,unit_amount,status,paid_at,token,last_issued_url',
            'payments:id,order_id,amount,currency,status,created_at',
        ]);

        // Get latest ACTIVE link
        $latestActiveLink = $order->paymentLinks()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('last_issued_at')
            ->orderByDesc('id')
            ->first();


        return response()->json([
            'status'  => true,
            'message' => 'Client invoice details fetched successfully',
            'data'    => [
                'order'          => $order->toArray(),   // will include all loaded relations
                'client'         => $client->toArray(),
                'lastActiveLink' => $latestActiveLink?->toArray(),
                'paymentLinks'   => $order->paymentLinks->toArray(), // always present
                'payments'       => $order->payments->toArray(),
            ],
        ]);
    }

    public function clientProfile(Request $request)
    {
        $user = $request->user(); // From Sanctum

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'No user logged in'], 401);
        }

        // Check if profile details exist
        $profile = ProfileDetail::where('user_id', $user->id)
            ->where('user_type', get_class($user))
            ->first();

        // Split name into first/last
        $fullName = $profile->name ?? $user->name;
        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0] ?? '';
        $lastName  = $parts[1] ?? '';

        return response()->json([
            'status'  => true,
            'message' => 'Client profile fetched successfully',
            'data'    => [
                'user'      => $user,
                'profile'   => $profile,
                'firstName' => $firstName,
                'lastName'  => $lastName,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user(); // From Sanctum

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'No user logged in.'
            ], 401);
        }

        $request->validate([
            'first_name'       => 'nullable|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'email'            => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($user->id),
            ],
            'alternate_email'  => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:20',
            'address'          => 'nullable|string|max:500',
            'profile'          => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'password'         => 'nullable|min:8|confirmed',
        ]);

        // Normalize alternate email
        $altEmail = $request->input('alternate_email')
            ? strtolower(trim($request->input('alternate_email')))
            : null;

        // Handle profile picture upload
        $fileName = null;
        if ($request->hasFile('profile')) {
            $file = $request->file('profile');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profiles/'), $fileName);
        }

        // Build full name
        $fullName = trim($request->input('first_name') . ' ' . $request->input('last_name'));

        /** Update only client table (name + email + password) */
        if ($fullName) {
            $user->name = $fullName;
        }
        if ($request->filled('email') && $request->email !== $user->email) {
            $user->email = strtolower(trim($request->email));
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        /** Update profile_details table */
        $profile = ProfileDetail::updateOrCreate(
            [
                'user_id'   => $user->id,
                'user_type' => get_class($user),
            ],
            [
                'name'            => $user->name,
                'email'           => $user->email,
                'alternate_email' => $altEmail,
                'phone'           => $request->input('phone'),
                'address'         => $request->input('address'),
                'profile'         => $fileName,
            ]
        );

        // If password changed → revoke token
        if ($request->filled('password')) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Password changed, please log in again.'
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully!',
            'data'    => [
                'user'    => $user,
                'profile' => $profile,
            ]
        ]);
    }
}
