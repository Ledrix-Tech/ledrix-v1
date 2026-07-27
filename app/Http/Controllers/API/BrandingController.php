<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\DirectOrderService;
use App\Services\LeadIntakeService;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    private function host(?string $url): ?string
    {
        return LeadIntakeService::normalizeHost($url);
    }

    private function brandFromUrl(?string $url): ?Brand
    {
        return app(LeadIntakeService::class)->resolveBrandFromHostUrl($url);
    }

    private function brandFromOrigin(Request $r): ?Brand
    {
        return app(LeadIntakeService::class)->resolveBrandFromRequestOrigin($r);
    }

    public function storeLead(Request $req, LeadIntakeService $intake)
    {
        $result = $intake->storeFromCrmPost($req);

        if ($result['duplicate']) {
            return response()->json([
                'ok'         => true,
                'duplicate'  => true,
                'lead_id'    => $result['lead']->id,
                'data'       => $result['lead'],
                'prediction' => $result['prediction'],
            ], 200);
        }

        return response()->json([
            'ok'         => true,
            'lead_id'    => $result['lead']->id,
            'seller_id'  => $result['seller']->id,
            'data'       => $result['lead'],
            'meta'       => $result['meta'],
            'prediction' => $result['prediction'],
        ], 201);
    }

    // public function storeLead(Request $req, LeadClassifier $classifier)
    // {
    //     $data = $req->validate([
    //         'name'   => 'required|string|max:255',
    //         'email'  => 'required|email|max:255',
    //         'phone'  => 'nullable|string|max:30',
    //         'service' => 'nullable|string|max:255',
    //         'price' => 'nullable|string',
    //         'message' => 'nullable|string|max:4000',
    //         'url'    => 'nullable|url',
    //         'utm_source' => 'nullable|string|max:100',
    //         'utm_medium' => 'nullable|string|max:100',
    //         'utm_campaign' => 'nullable|string|max:150',
    //         'referrer' => 'nullable|string|max:2048',
    //         'session_id' => 'nullable|string|max:64',
    //     ]);
    //     // dd($req->all());

    //     // Run classification
    //     $prediction = $classifier->classify($data);       // "real" or "fake"
    //     // if ($prediction['score'] < 50 || $prediction['status'] === 'spam') {
    //     //     return response()->json([
    //     //         'ok' => false,
    //     //         'rejected' => true,
    //     //         'reason' => 'Low quality or spam lead',
    //     //         'prediction' => $prediction,
    //     //     ], 200); // not an error, just a silent reject
    //     // }

    //     // Brand resolution (unchanged)
    //     $brand = $this->brandFromUrl($data['url'] ?? null) ?? $this->brandFromOrigin($req);
    //     abort_unless($brand, 422, 'Unknown brand');
    //     $idem = $req->header('Idempotency-Key');
    //     if ($idem && Lead::where('brand_id', $brand->id)->where('meta->idem', $idem)->exists()) {
    //         return response()->json(['ok' => true, 'duplicate' => true], 200);
    //     }
    //     $client = Client::firstOrCreate(
    //         ['email' => strtolower(trim($data['email']))],
    //         [
    //             'name'  => $data['name'] ?? null,
    //             'phone' => $data['phone'] ?? null,
    //         ]
    //     );

    //     $seller = app(LeadAssigner::class)->assignNext($brand);

    //     $lead = Lead::create([
    //         'brand_id'   => $brand->id,
    //         'seller_id'  => $seller->id,
    //         'client_id'  => $client->id,
    //         'name'       => $client['name'],
    //         'email'      => $client['email'],
    //         'phone'      => $client['phone'] ?? null,
    //         'message'    => $data['message'] ?? null,
    //         'status'     => 'new',
    //         'prediction' => json_encode($prediction),
    //         'domain_url' => $this->host($data['url'] ?? ($req->headers->get('Referer') ?: '')),
    //         'meta' => array_filter([
    //             'utm_source'   => $data['utm_source'] ?? null,
    //             'utm_medium'   => $data['utm_medium'] ?? null,
    //             'utm_campaign' => $data['utm_campaign'] ?? null,
    //             'page_title'   => $data['page_title'] ?? null,
    //             'timezone'     => $data['timezone'] ?? null,
    //             'locale'       => $data['locale'] ?? null,
    //             'channel'      => $data['channel'] ?? null,
    //             'preferred_contact' => $data['preferred_contact'] ?? null,
    //             'contact_time'      => $data['contact_time'] ?? null,
    //             'company'      => $data['company'] ?? null,
    //             'currency'     => $data['currency'] ?? 'USD',
    //             'service'      => $data['service'] ?? null,
    //             'price'        => $data['price'] ?? null,
    //             'session_id'   => $data['session_id'] ?? null,
    //             'idem'         => $idem,
    //             'ip'           => $req->ip(),
    //             'ua'           => substr((string)$req->userAgent(), 255),
    //         ]),
    //     ]);

    //     return response()->json(['ok' => true, 'data' => $lead, 'lead_id' => $lead->id, 'prediction' => $prediction], 201);
    // }

    public function directOrder(Request $request, DirectOrderService $directOrder)
    {
        return $directOrder->handle($request);
    }
}
