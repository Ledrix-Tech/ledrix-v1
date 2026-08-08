<?php

namespace App\Http\Controllers\API\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Questionnair;
use App\Services\BriefService;
use App\Support\BriefServiceCatalog;
use App\Support\PortalAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BriefController extends Controller
{
    public function clientBriefs()
    {
        $client = auth('client')->user();

        $orders = Order::query()
            ->with(['brand:id,brand_name', 'seller:id,name', 'client:id,name,email', 'brief'])
            ->where('client_id', $client->id)
            ->where('order_type', 'original')
            ->whereIn('service_name', BriefServiceCatalog::questionnaireServiceNames())
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('clients.pages.briefs', compact('orders'));
    }

    public function sellerBriefsHub(BriefService $briefService)
    {
        PortalAuthorization::requirePortalActor();

        $seller = auth('seller')->user();
        abort_unless($seller, 403);

        $query = Order::query()
            ->with(['brand:id,brand_name', 'client:id,name,email', 'brief'])
            ->where('order_type', 'original');

        $role = $seller->role ?? $seller->is_seller;

        if ($role === 'front_seller') {
            $query->where('brand_id', $seller->brand_id);
        } else {
            $query->where(function ($w) use ($seller) {
                $w->where('seller_id', $seller->id)
                    ->orWhere('owner_seller_id', $seller->id);
            });
        }

        $orders = $query->latest('id')->get();
        $filteredOrders = BriefServiceCatalog::filterOrdersForBriefs($orders);

        $rows = $filteredOrders->map(function (Order $order) use ($briefService) {
            $status = BriefServiceCatalog::briefStatus($order->brief);
            $brief = $order->brief ?? $briefService->ensureBriefForOrder($order);

            return [
                'order' => $order,
                'status' => $status,
                'public_url' => $briefService->publicBriefUrl($brief),
            ];
        });

        $stats = [
            'total' => $rows->count(),
            'pending' => $rows->where('status', 'pending')->count(),
            'in_progress' => $rows->where('status', 'in_progress')->count(),
            'completed' => $rows->where('status', 'completed')->count(),
        ];

        return view('sellers.pages.briefs-hub', [
            'rows' => $rows,
            'stats' => $stats,
            'clientPortalUrl' => $briefService->clientPortalBriefUrl(),
        ]);
    }

    public function showBriefForm(string $token)
    {
        $brief = Questionnair::query()
            ->where('brief_token', $token)
            ->where(function ($q) {
                $q->whereNull('brief_token_expires_at')
                    ->orWhere('brief_token_expires_at', '>', now());
            })
            ->with(['order:id,client_id,service_name,brand_id'])
            ->firstOrFail();

        // If client is logged in, ensure they own it (extra protection)
        if (auth('client')->check()) {
            abort_unless((int)$brief->client_id === (int)auth('client')->id(), 403, 'Unauthorized brief access.');
        }

        return view('clients.pages.brief-token', [
            'order'        => $brief->order,
            'brief'        => $brief->meta ?? [],
            'questionnair' => $brief,
            'token'        => $token,
            'mode'         => 'token',
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $brief = Questionnair::query()
            ->where('brief_token', $token)
            ->where(function ($q) {
                $q->whereNull('brief_token_expires_at')
                    ->orWhere('brief_token_expires_at', '>', now());
            })
            ->with('order:id,client_id')
            ->firstOrFail();

        if (auth('client')->check()) {
            abort_unless((int)$brief->client_id === (int)auth('client')->id(), 403, 'Unauthorized brief access.');
        }

        abort_if($brief->status === 'completed', 403, 'This brief is completed and can no longer be edited.');

        $validated = $request->validate([
            'query' => ['required', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,zip', 'max:10240'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['string'],
        ]);

        $this->saveBriefMetaAndFiles($brief, $validated, $request);

        $brief->status = 'progress';
        $brief->save();

        return back()->with('success', 'Your project info was submitted successfully!');
    }

    private function saveBriefMetaAndFiles(Questionnair $brief, array $validated, Request $request): void
    {
        $meta = $brief->meta ?? [];

        // ✅ remove attachments securely (only allow removing files that belong to this brief)
        if (!empty($validated['remove_attachments'])) {
            $existing = $meta['attachments'] ?? [];
            $toRemove = array_values(array_intersect($existing, $validated['remove_attachments']));

            foreach ($toRemove as $relPath) {
                // store only relative paths like "uploads/brief-attachments/xyz.png"
                Storage::disk('public')->delete($relPath);
            }
            $meta['attachments'] = array_values(array_diff($existing, $toRemove));
        }

        // ✅ add new uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension    = $file->getClientOriginalExtension();

                $safeName = Str::slug($originalName) . '_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.' . $extension;

                $path = $file->storeAs('uploads/brief-attachments', $safeName, 'public');

                // store RELATIVE path, not "storage/..."
                $meta['attachments'][] = $path;
            }
        }

        // ✅ store query only (not arbitrary request keys)
        $meta = array_merge($meta, ['query' => $validated['query']]);

        $brief->meta = $meta;
        $brief->save();
    }

    public function clientBriefPost(Request $request)
    {
        $client = auth('client')->user();

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'query' => ['required', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,zip', 'max:10240'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['string'],
        ]);

        $order = Order::select(['id', 'client_id', 'service_name'])->findOrFail($validated['order_id']);
        abort_unless((int)$order->client_id === (int)$client->id, 403);
        abort_unless(BriefServiceCatalog::hasQuestionnaire($order->service_name), 422, 'No questionnaire for this service.');

        $existing = Questionnair::query()->where('order_id', $order->id)->first();
        if ($existing && $existing->status === 'completed') {
            throw ValidationException::withMessages([
                'order_id' => 'This brief is completed and can no longer be edited.',
            ]);
        }

        $brief = Questionnair::updateOrCreate(
            ['order_id' => $order->id],
            [
                'client_id' => $client->id,
                'service_name' => $order->service_name ?? 'Unknown Service',
            ]
        );

        $this->saveBriefMetaAndFiles($brief, $validated, $request);

        $brief->status = 'progress';
        $brief->save();

        return back()->with('success', 'Project info submitted successfully!');
    }

    public function sellerBriefStatusPost(Request $request, BriefService $briefService)
    {
        PortalAuthorization::requirePortalActor();

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required', Rule::in(['pending', 'progress', 'completed'])],
        ]);

        $order = Order::query()->with('brief')->findOrFail($validated['order_id']);
        PortalAuthorization::authorizeOrder($order);

        $brief = $order->brief ?? $briefService->ensureBriefForOrder($order);
        $brief->status = $validated['status'];
        $brief->save();

        return back()->with('success', 'Brief status updated.');
    }
}
