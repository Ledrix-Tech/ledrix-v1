@extends('admin.layout.layout')

@section('title', 'Admin | Domain Scripts')

@section('admin-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h1 class="fw-bold mb-1" style="color: #003C51;">Domain Scripts</h1>
                        <p class="text-muted mb-0">
                            Configure field mapping per brand, embed the snippet on each LLC site, and test capture from here.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row my-4">
            <div class="col-lg-12">
                <div class="alert alert-info mb-4">
                    <strong>How it works:</strong>
                    Add <code>&lt;form id="lead-form"&gt;</code> on the brand site, paste the embed snippet below
                    (includes automatic inline fallback if the dynamic script fails to load).
                </div>

                @forelse ($brands as $brand)
                    <form method="POST" action="{{ route('admin.domain-scripts-update', $brand->id) }}"
                        class="card shadow-sm mb-4 brand-script-card" data-brand-id="{{ $brand->id }}">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <div>
                                    <h5 class="fw-bold text-info mb-1">{{ $brand->brand_name }}</h5>
                                    <p class="small text-muted mb-0">
                                        Domain:
                                        <a href="{{ $brand->brand_url }}" target="_blank">{{ $brand->brand_url }}</a>
                                        @if ($brand->brand_host)
                                            · Host: <code>{{ $brand->brand_host }}</code>
                                        @endif
                                    </p>
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-secondary script-status-badge"
                                        data-status-url="{{ route('admin.domain-scripts.script-status', $brand) }}">Checking…</span>
                                    <button type="button" class="btn btn-sm btn-outline-dark test-lead-btn"
                                        data-url="{{ route('admin.domain-scripts.test-lead', $brand->id) }}"
                                        data-brand-id="{{ $brand->id }}">
                                        Test lead capture
                                    </button>
                                </div>
                            </div>

                            <div class="test-result alert d-none mb-3" data-brand-id="{{ $brand->id }}"></div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Embed on brand site</label>
                                <p class="small text-muted mb-2">
                                    Includes dynamic script + inline fallback (activates after 6s or if the .js fails to load).
                                </p>
                                <textarea class="form-control font-monospace embed-snippet" rows="6" readonly>{{ $scriptService->embedSnippetForBrand($brand) }}</textarea>
                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm copy-embed">Copy embed</button>
                                    <a href="{{ $scriptService->scriptUrlForBrand($brand) }}" target="_blank"
                                        class="btn btn-outline-secondary btn-sm">Open script URL</a>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Field mapping</label>
                                <p class="small text-muted">Left = CRM field. Right = field name on the brand website form.</p>

                                <div id="field-mapping-container-{{ $brand->id }}" class="border rounded p-3 bg-light">
                                    @php
                                        $mapping = $brand->field_mapping ?: \App\Services\LeadScriptService::defaultFieldMapping();
                                    @endphp

                                    @foreach ($mapping as $crmField => $siteField)
                                        <div class="d-flex mb-2 gap-2 field-map-row">
                                            <input type="text" name="data_fields[crm_field][]" class="form-control"
                                                value="{{ $crmField }}" placeholder="CRM field (name)">
                                            <input type="text" name="data_fields[site_field][]" class="form-control"
                                                value="{{ $siteField }}" placeholder="Website field (full_name)">
                                            <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-outline-info btn-sm mt-2 add-field-row"
                                    data-target="field-mapping-container-{{ $brand->id }}">
                                    Add field
                                </button>
                            </div>

                            <details class="mb-3">
                                <summary class="fw-bold">Advanced: custom script override (optional)</summary>
                                <p class="small text-muted mt-2">
                                    Leave empty to use the universal Ledrix script. Only use this if a brand needs fully custom JS.
                                </p>
                                <textarea name="lead_script" class="form-control font-monospace" rows="8">{{ old('lead_script', $brand->lead_script) }}</textarea>
                            </details>

                            <button type="submit" class="btn btn-success">
                                Save settings
                            </button>
                        </div>
                    </form>
                @empty
                    <div class="alert alert-warning">No brands found. Create a brand first.</div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.copy-embed').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var textarea = btn.closest('.card-body').querySelector('.embed-snippet');
                navigator.clipboard.writeText(textarea.value).then(function () {
                    btn.textContent = 'Copied';
                    setTimeout(function () { btn.textContent = 'Copy embed'; }, 2000);
                });
            });
        });

        document.querySelectorAll('.add-field-row').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var container = document.getElementById(btn.dataset.target);
                var row = document.createElement('div');
                row.className = 'd-flex mb-2 gap-2 field-map-row';
                row.innerHTML = `
                    <input type="text" name="data_fields[crm_field][]" class="form-control" placeholder="CRM field">
                    <input type="text" name="data_fields[site_field][]" class="form-control" placeholder="Website field">
                    <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
                `;
                container.appendChild(row);
            });
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                var row = e.target.closest('.field-map-row');
                var container = row.parentElement;
                if (container.querySelectorAll('.field-map-row').length > 1) {
                    row.remove();
                }
            }
        });

        document.querySelectorAll('.test-lead-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var brandId = btn.dataset.brandId;
                var resultBox = document.querySelector('.test-result[data-brand-id="' + brandId + '"]');
                btn.disabled = true;
                btn.textContent = 'Testing…';

                fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                    },
                })
                    .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
                    .then(function (result) {
                        resultBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                        if (result.ok && result.body.ok) {
                            resultBox.classList.add('alert-success');
                            resultBox.textContent = result.body.message + ' Lead #' + result.body.lead_id + ' (' + result.body.email + ')';
                        } else {
                            resultBox.classList.add('alert-danger');
                            resultBox.textContent = result.body.message || 'Test failed.';
                        }
                    })
                    .catch(function () {
                        resultBox.classList.remove('d-none', 'alert-success');
                        resultBox.classList.add('alert-danger');
                        resultBox.textContent = 'Network error while testing lead capture.';
                    })
                    .finally(function () {
                        btn.disabled = false;
                        btn.textContent = 'Test lead capture';
                    });
            });
        });

        document.querySelectorAll('.script-status-badge').forEach(function (badge) {
            fetch(badge.dataset.statusUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.script_active) {
                        badge.className = 'badge bg-success script-status-badge';
                        badge.textContent = 'Script active';
                    } else {
                        badge.className = 'badge bg-warning text-dark script-status-badge';
                        badge.textContent = 'Script inactive (subscription/API)';
                    }
                })
                .catch(function () {
                    badge.className = 'badge bg-secondary script-status-badge';
                    badge.textContent = 'Status unknown';
                });
        });
    </script>
@endsection
