<?php

namespace App\Services;

use App\Models\Brand;

class LeadScriptService
{
    /** @return array<string, string> CRM field => website form field name */
    public function fieldMappingForBrand(Brand $brand): array
    {
        $mapping = $brand->field_mapping;

        if (! is_array($mapping) || $mapping === []) {
            return self::defaultFieldMapping();
        }

        return $mapping;
    }

    /** @return array<string, string> */
    public static function defaultFieldMapping(): array
    {
        return [
            'name'    => 'name',
            'email'   => 'email',
            'phone'   => 'phone',
            'service' => 'service',
            'message' => 'message',
        ];
    }

    public function scriptUrlForBrand(Brand $brand): string
    {
        $host = $brand->brand_host ?: ('brand-' . $brand->id);

        return route('lead.script', ['host' => $host]);
    }

    public function embedSnippetForBrand(Brand $brand): string
    {
        $scriptUrl = $this->scriptUrlForBrand($brand);
        $fallbackScript = $this->renderUniversalScript($brand);

        return implode("\n", [
            sprintf('<script src="%s" defer id="ledrix-lead-script" onerror="window.__ledrixActivateFallback&&window.__ledrixActivateFallback()"></script>', e($scriptUrl)),
            '<script type="text/ledrix-fallback" id="ledrix-fallback-src">' . $fallbackScript . '</script>',
            $this->fallbackLoaderScript(),
        ]);
    }

    /** Inline loader: activates backup script if dynamic .js fails or times out. */
    public function fallbackLoaderScript(): string
    {
        return <<<'HTML'
<script>
(function () {
    window.__ledrixActivateFallback = function () {
        if (window.__ledrixLeadCapture) return;
        var src = document.getElementById('ledrix-fallback-src');
        if (!src || !src.textContent) return;
        var s = document.createElement('script');
        s.textContent = src.textContent;
        document.head.appendChild(s);
        window.__ledrixLeadCapture = 'fallback';
        console.warn('[Ledrix] Using inline fallback lead capture script.');
    };
    setTimeout(function () {
        if (!window.__ledrixLeadCapture) {
            window.__ledrixActivateFallback();
        }
    }, 6000);
})();
</script>
HTML;
    }

    public function resolveBrandFromHost(string $host): ?Brand
    {
        $host = strtolower(preg_replace('/^www\./i', '', str_replace('.js', '', trim($host))));

        if ($host === '') {
            return null;
        }

        return Brand::withoutGlobalScopes()
            ->where(function ($query) use ($host) {
                $query->where('brand_host', $host)
                    ->orWhereJsonContains('allowed_origins', $host)
                    ->orWhereJsonContains('allowed_origins', 'www.' . $host);
            })
            ->first();
    }

    public function renderForBrand(Brand $brand): string
    {
        if ($brand->lead_script) {
            return $this->renderCustomScript($brand);
        }

        return $this->renderUniversalScript($brand);
    }

    public function renderUniversalScript(Brand $brand): string
    {
        $config = json_encode([
            'endpoint'     => route('crm.leads.post'),
            'brandKey'     => $brand->public_form_token,
            'formSelector' => '#lead-form',
            'fieldMapping' => $this->fieldMappingForBrand($brand),
            'honeypot'     => 'website',
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return str_replace('__CONFIG__', $config, $this->universalScriptTemplate());
    }

    private function renderCustomScript(Brand $brand): string
    {
        $replacements = [
            '{{ crm_endpoint }}', '{{crm_endpoint}}',
            '{{ brand_key }}', '{{brand_key}}',
            '{{ brand_host }}', '{{brand_host}}',
        ];
        $values = [
            route('crm.leads.post'), route('crm.leads.post'),
            $brand->public_form_token, $brand->public_form_token,
            $brand->brand_host ?? '', $brand->brand_host ?? '',
        ];

        return str_replace($replacements, $values, (string) $brand->lead_script);
    }

    private function universalScriptTemplate(): string
    {
        return <<<'JS'
(function () {
    window.__ledrixLeadCapture = 'dynamic';
    var C = __CONFIG__;
    var form = document.querySelector(C.formSelector || '#lead-form');
    if (!form || form.dataset.ledrixBound) return;
    form.dataset.ledrixBound = '1';

    var cidKey = 'ledrix_cid';

    function getCid() {
        var cid = localStorage.getItem(cidKey);
        if (!cid && window.crypto && crypto.randomUUID) {
            cid = crypto.randomUUID();
            localStorage.setItem(cidKey, cid);
        }
        return cid || undefined;
    }

    function getUTM() {
        var p = new URLSearchParams(location.search);
        function pick(k) { var v = p.get(k); return v || undefined; }
        return {
            utm_source: pick('utm_source'),
            utm_medium: pick('utm_medium'),
            utm_campaign: pick('utm_campaign'),
        };
    }

    function val(formEl, key) {
        var el = formEl.namedItem(key);
        if (!el) return undefined;
        var v = String(el.value || '').trim();
        return v || undefined;
    }

    function mapCoreFields(f) {
        var out = {};
        var mapping = C.fieldMapping || {};
        Object.keys(mapping).forEach(function (crmKey) {
            var siteKey = mapping[crmKey];
            var v = val(f, siteKey);
            if (v !== undefined) out[crmKey] = v;
        });
        return out;
    }

    function isBot(f) {
        var hp = C.honeypot || 'website';
        var trap = f.namedItem(hp);
        return trap && String(trap.value || '').trim().length > 0;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var f = form.elements;
        var btn = form.querySelector('[type="submit"]');
        if (btn) btn.setAttribute('disabled', 'disabled');

        if (isBot(f)) {
            if (btn) btn.removeAttribute('disabled');
            return;
        }

        var core = mapCoreFields(f);
        if (!core.name || !core.email) {
            alert('Please fill in name and email.');
            if (btn) btn.removeAttribute('disabled');
            return;
        }

        var payload = Object.assign({}, core, getUTM(), {
            brand_key: C.brandKey,
            channel: val(f, 'channel') || 'web_form',
            url: location.origin + '/',
            brand_host: location.hostname,
            referrer: document.referrer || undefined,
            page_title: document.title,
            timezone: (Intl.DateTimeFormat().resolvedOptions().timeZone) || undefined,
            locale: navigator.language || undefined,
            session_id: getCid(),
            price: val(f, 'price') || undefined,
            currency: (val(f, 'currency') || 'USD').toUpperCase(),
            preferred_contact: val(f, 'preferred_contact'),
            contact_time: val(f, 'contact_time'),
            company: val(f, 'company'),
        });

        fetch(C.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Idempotency-Key': (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()),
            },
            body: JSON.stringify(payload),
            mode: 'cors',
            credentials: 'omit',
        })
            .then(function (res) {
                var ct = res.headers.get('content-type') || '';
                return (ct.indexOf('application/json') >= 0 ? res.json() : res.text()).then(function (body) {
                    return { ok: res.ok, status: res.status, body: body };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    var msg = (result.body && result.body.message) ? result.body.message : ('Failed (' + result.status + ')');
                    alert(msg);
                    return;
                }
                form.reset();
                alert('Thanks! We received your request.');
            })
            .catch(function (err) {
                console.error(err);
                alert('Network error. Please try again.');
            })
            .finally(function () {
                if (btn) btn.removeAttribute('disabled');
            });
    });
})();
JS;
    }
}
