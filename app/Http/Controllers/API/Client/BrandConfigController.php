<?php

namespace App\Http\Controllers\API\Client;

use App\Models\Brand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class BrandConfigController extends Controller
{
    public function showScript($host)
    {
        $brand = Brand::where('brand_url', 'like', "%$host%")->firstOrFail();

        $script = str_replace(
            ['{{ crm_endpoint }}', '{{ brand_key }}', '{{ brand_host }}'],
            [route('crm.leads.post'), $brand->public_form_token, $brand->brand_url],
            $brand->lead_script
        );

        return response($script, 200)->header('Content-Type', 'application/javascript');
    }


    public function adminDomainScripts()
    {
        $brands = Brand::all();
        return view('admin.pages.domain-script', compact('brands'));
    }

    public function domainScriptStore(Request $request)
    {
        $data = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'lead_script' => 'required|string',
            'data_fields.crm_field' => 'nullable|array',
            'data_fields.site_field' => 'nullable|array',
        ]);

        $brand = Brand::findOrFail($data['brand_id']);
        // dd($request->all(),$data,$brand);

        // Build clean field mapping (CRM field -> Website field)
        $mapping = [];
        $crmFields = $request->input('data_fields.crm_field', []);
        $siteFields = $request->input('data_fields.site_field', []);

        foreach ($crmFields as $i => $crmKey) {
            $crmKey = trim($crmKey);
            $siteKey = trim($siteFields[$i] ?? '');
            if ($crmKey && $siteKey) {
                $mapping[$crmKey] = $siteKey;
            }
        }

        // Optional: backup previous data before overwriting
        if ($brand->lead_script || $brand->field_mapping) {
            Storage::disk('local')->put(
                "backups/brand_scripts/{$brand->id}_" . now()->format('Ymd_His') . ".json",
                json_encode([
                    'lead_script' => $brand->lead_script,
                    'field_mapping' => $brand->field_mapping,
                ], JSON_PRETTY_PRINT)
            );
        }

        // Update Brand
        $brand->update([
            'lead_script' => $data['lead_script'],
            'field_mapping' => $mapping,
        ]);

        return back()->with('success', "✅ Script and field mapping updated for {$brand->brand_name}!");
    }

    public function domainScriptUpdate(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'lead_script' => 'required|string',
            'data_fields.crm_field' => 'nullable|array',
            'data_fields.site_field' => 'nullable|array',
        ]);

        $mapping = [];
        $crmFields  = $request->input('data_fields.crm_field', []);
        $siteFields = $request->input('data_fields.site_field', []);
        foreach ($crmFields as $i => $crmKey) {
            $crmKey = trim($crmKey);
            $siteKey = trim($siteFields[$i] ?? '');
            if ($crmKey && $siteKey) {
                $mapping[$crmKey] = $siteKey;
            }
        }

        // Backup previous version
        Storage::disk('local')->put(
            "backups/brand_scripts/{$brand->id}_" . now()->format('Ymd_His') . ".json",
            json_encode([
                'lead_script' => $brand->lead_script,
                'field_mapping' => $brand->data_fields,
            ], JSON_PRETTY_PRINT)
        );

        $brand->update([
            'lead_script' => $validated['lead_script'],
            'field_mapping' => $mapping,
        ]);

        return back()->with('success', "✅ Updated script for {$brand->brand_name}.");
    }

    public function serveDomainScript($host)
    {
        // Normalize domain
        $host = str_replace(['.js', 'www.'], '', $host);

        // $brand = Brand::where('brand_host', 'like', "%$host%")->first();
        $brand = Brand::where('brand_url', 'like', "%{$host}%")
            ->orWhere('brand_name', 'like', "%{$host}%")
            ->orWhere('brand_url', 'like', "%127.0.0.1%{$host}%")
            ->firstOrFail();


        if (!$brand) {
            $fallback = $this->defaultScript();
            return response($fallback, 200)->header('Content-Type', 'application/javascript');
        }

        // Replace placeholders in the script
        $script = $brand->lead_script ?? $this->defaultScript();
        $script = str_replace(
            ['{{crm_endpoint}}', '{{brand_key}}', '{{brand_host}}'],
            [
                route('crm.leads.post'),
                $brand->brand_key ?? $brand->id,
                $brand->brand_host,
            ],
            $script
        );

        return response($script, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=300');
    }

    protected function defaultScript(): string
    {
        return <<<JS
        (() => {
        const form = document.querySelector('#lead-form');
        if (!form) return;
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(form));
            data.brand_key = '{{brand_key}}';
            try {
            const res = await fetch('{{crm_endpoint}}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.ok) alert('Thank you! We received your request.');
            else alert('Error: ' + json.message);
            } catch (err) {
            console.error(err);
            alert('Network error');
            }
        });
        })();
        JS;
    }
}
