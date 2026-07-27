@extends('admin.layout.layout')

@section('title', 'Admin | Scripts')

@section('admin-content')


    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <h1 class="fw-bold" style="color: #003C51;">Domain Scripts</h1>
                    <div class="d-flex">
                        <div class="d-flex">
                            <button type="submit" class="btn bg-gradient-3" data-toggle="modal" data-target="#addScript">Add Script</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">
                <div class="row">
                    @foreach ($brands as $brand)
                        <div class="col-md-6 mb-4">
                            <form method="POST" action="{{ route('admin.domain-scripts-update', $brand->id) }}"
                                class="card p-3 shadow-sm mb-4">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <!-- Brand Info -->
                                    <div class="col-lg-12 mb-3">
                                        <h5 class="fw-bold mb-1 text-info">{{ $brand->brand_name }}</h5>
                                        <p class="small text-muted mb-2">
                                            Domain:
                                            <a href="{{ $brand->brand_url }}" target="_blank">{{ $brand->brand_url }}</a>
                                        </p>
                                    </div>

                                    <!-- Lead Field Mapping -->
                                    <div class="col-lg-12 mb-3">
                                        <label class="form-label fw-bold">Lead Field Mapping</label>

                                        <div id="field-mapping-container-{{ $brand->id }}"
                                            class="border rounded p-3 bg-light">
                                            @php
                                                $mapping = $brand->lead_field_mapping ?? [];
                                            @endphp

                                            @forelse($mapping as $crmField => $siteField)
                                                <div class="d-flex mb-2 field-map-row">
                                                    <input type="text" name="data_fields[crm_field][]"
                                                        class="form-control me-2" value="{{ $crmField }}"
                                                        placeholder="CRM Field (e.g. name)">
                                                    <input type="text" name="data_fields[site_field][]"
                                                        class="form-control me-2" value="{{ $siteField }}"
                                                        placeholder="Website Field (e.g. full_name)">
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-row">✕</button>
                                                </div>
                                            @empty
                                                <div class="d-flex mb-2 field-map-row">
                                                    <input type="text" name="data_fields[crm_field][]"
                                                        class="form-control me-2" placeholder="CRM Field (e.g. name)">
                                                    <input type="text" name="data_fields[site_field][]"
                                                        class="form-control me-2"
                                                        placeholder="Website Field (e.g. full_name)">
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-row">✕</button>
                                                </div>
                                            @endforelse
                                        </div>

                                        <button type="button" class="btn btn-outline-info btn-sm mt-2 add-field-row"
                                            data-target="field-mapping-container-{{ $brand->id }}">
                                            ➕ Add Field
                                        </button>

                                        <small class="text-muted d-block mt-2">
                                            Map CRM fields (left) to website fields (right).
                                        </small>
                                    </div>

                                    <!-- Lead Script Editor -->
                                    <div class="col-lg-12 mb-3">
                                        <label class="form-label fw-bold">Custom Lead Script (JS)</label>
                                        <textarea id="lead_script_{{ $brand->id }}" name="lead_script"
                                            class="lead_script code-editor form-control" rows="12">{!! old('lead_script', $brand->lead_script) !!}</textarea>
                                        <small class="text-muted d-block mt-2">
                                            Use placeholders:
                                            <code>@{{ crm_endpoint }}</code>,
                                            <code>@{{ brand_key }}</code>,
                                            <code>@{{ brand_host }}</code>
                                            — they’ll be replaced dynamically when served.
                                        </small>
                                    </div>
                                    <!-- Submit -->
                                    <div class="col-lg-12 text-center mt-3">
                                        <button class="btn btn-success w-50">
                                            <i class="fas fa-save me-1"></i> Update Script
                                        </button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="addScript" data-backdrop="static" data-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Domain Scripts</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('admin.domain-scripts.post') }}">
                        @csrf
                        <div class="row">
                            <!-- Brand Select -->
                            <div class="col-lg-12 mb-3">
                                <label class="form-label fw-bold">Select Brand / Domain</label>
                                <select name="brand_id" class="form-control" required>
                                    <option value="">-- Select Domain --</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">
                                            {{ $brand->brand_name }} - ({{ $brand->brand_url }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Field Mapping -->
                            <div class="col-lg-12 mb-3">
                                <label class="form-label fw-bold">Lead Field Mapping</label>
                                <div id="field-mapping-container" class="border rounded p-3 bg-light">
                                    <div class="d-flex mb-2 field-map-row">
                                        <input type="text" name="data_fields[crm_field][]" class="form-control me-2"
                                            placeholder="CRM Field (e.g. name)">
                                        <input type="text" name="data_fields[site_field][]" class="form-control me-2"
                                            placeholder="Website Field (e.g. full_name)">
                                        <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
                                    </div>
                                </div>
                                <button type="button" id="add-field-row" class="btn btn-info btn-sm mt-2">➕ Add
                                    Field</button>
                                <small class="text-muted d-block mt-2">
                                    Define how this brand’s form fields map to CRM fields.
                                </small>
                            </div>

                            <!-- Lead Script Editor -->
                            <div class="col-lg-12 mb-3">
                                <label class="form-label fw-bold">Custom Lead Script (JS)</label>
                                <textarea id="lead_script" name="lead_script" class="code-editor form-control" rows="15">{{ old('lead_script') }}</textarea>
                                <small class="text-muted d-block mt-2">
                                    Use placeholders:
                                    <code>@{{ crm_endpoint }}</code>,
                                    <code>@{{ brand_key }}</code>,
                                    <code>@{{ brand_host }}</code>
                                </small>
                            </div>
                            <!-- Submit -->
                            <div class="col-lg-12 text-center mt-3">
                                <button class="btn btn-success w-50">💾 Save Script</button>
                            </div>
                        </div>
                    </form>

                    <!-- Dynamic JS for Field Mapping -->
                    <script>
                        document.getElementById('add-field-row').addEventListener('click', function() {
                            const container = document.getElementById('field-mapping-container');
                            const newRow = document.createElement('div');
                            newRow.className = 'd-flex mb-2 field-map-row';
                            newRow.innerHTML = `
                                    <input type="text" name="data_fields[crm_field][]" class="form-control me-2" placeholder="CRM Field (e.g. name)">
                                    <input type="text" name="data_fields[site_field][]" class="form-control me-2" placeholder="Website Field (e.g. full_name)">
                                    <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
                                `;
                            container.appendChild(newRow);
                        });

                        document.addEventListener('click', function(e) {
                            if (e.target.classList.contains('remove-row')) {
                                e.target.closest('.field-map-row').remove();
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function(e) {
            // Add new mapping row
            if (e.target.classList.contains('add-field-row')) {
                const containerId = e.target.dataset.target;
                const container = document.getElementById(containerId);
                const newRow = document.createElement('div');
                newRow.className = 'd-flex mb-2 field-map-row';
                newRow.innerHTML = `
            <input type="text" name="data_fields[crm_field][]" class="form-control me-2" placeholder="CRM Field (e.g. name)">
            <input type="text" name="data_fields[site_field][]" class="form-control me-2" placeholder="Website Field (e.g. full_name)">
            <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
        `;
                container.appendChild(newRow);
            }

            // Remove mapping row
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('.field-map-row').remove();
            }
        });
    </script>

    <!-- CodeMirror Styles + JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/material-darker.min.css">
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.code-editor').forEach((textarea, i) => {
                if (!textarea.id) textarea.id = 'code_editor_' + i;

                const editor = CodeMirror.fromTextArea(textarea, {
                    mode: 'javascript',
                    theme: 'material-darker',
                    lineNumbers: true,
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    indentUnit: 4,
                    tabSize: 4,
                    lineWrapping: true
                });

                // Keep editor value synced with textarea before submitting form
                textarea.closest('form').addEventListener('submit', () => {
                    textarea.value = editor.getValue();
                });
            });
        });
    </script>

    {{-- <script>
        (function() {
            const form = document.getElementById('lead-form');
            if (!form) return;

            const CRM_ENDPOINT = "{{ crm_endpoint }}"; // ✅ dynamic endpoint</>
            const cidKey = 'crm_cid';

            function getCid() {
                let cid = localStorage.getItem(cidKey);
                if (!cid) {
                    cid = crypto.randomUUID();
                    localStorage.setItem(cidKey, cid);
                }
                return cid;
            }

            function getUTM() {
                const p = new URLSearchParams(location.search);
                const pick = k => (p.get(k) || undefined);
                return {
                    utm_source: pick('utm_source'),
                    utm_medium: pick('utm_medium'),
                    utm_campaign: pick('utm_campaign'),
                };
            }
            const honeypotName = 'website';

            function isBot(f) {
                return (f.namedItem(honeypotName)?.value || '').trim().length > 0;
            }
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const f = form.elements;
                const btn = form.querySelector('[type="submit"]');
                btn?.setAttribute('disabled', 'disabled');

                const name = (f.namedItem('name')?.value || '').trim();
                const email = (f.namedItem('email')?.value || '').trim();
                if (!name || !email) {
                    alert('Please fill in name and email.');
                    btn?.removeAttribute('disabled');
                    return;
                }
                if (isBot(f)) {
                    btn?.removeAttribute('disabled');
                    return;
                }
                const currency = (f.namedItem('currency')?.value || 'USD').toUpperCase();

                const payload = {
                    name,
                    email,
                    phone: (f.namedItem('phone')?.value || '').trim() || undefined,
                    service: (f.namedItem('service')?.value || '').trim() || null,
                    price: (f.namedItem('price')?.value || '').trim() || null,
                    message: (f.namedItem('message')?.value || '').trim() || undefined,
                    ...getUTM(),
                    brand_key: "{{ brand_key }}", // ✅ replaced dynamically
                    channel: (f.namedItem('channel')?.value || '').trim() || 'web_form',
                    url: location.origin || "{{ brand_host }}",
                    referrer: document.referrer || undefined,
                    page_title: document.title,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    locale: navigator.language || undefined,
                    session_id: getCid(),
                };

                try {
                    const res = await fetch(CRM_ENDPOINT, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Idempotency-Key': crypto.randomUUID(),
                        },
                        body: JSON.stringify(payload),

                        mode: 'cors',
                        credentials: 'omit',
                    });
                    const ct = res.headers.get('content-type') || '';
                    const body = ct.includes('application/json') ? await res.json() : await res
                        .text();
                    if (!res.ok) {

                        console.error('CRM error', res.status, body);
                        alert(body?.message ||
                            `Failed (${res.status}). See console for details.`);
                        return;
                    }
                    console.log('✅ Lead submitted:', body);
                    form.reset();
                    alert('Thanks! We received your request.');
                } catch (err) {
                    console.error(err);
                    alert('Network error. Please try again.');
                } finally {
                    btn?.removeAttribute('disabled');
                }
            });
        })();
    </script> --}}


    <!-- CKEditor 4 (Free CDN) -->
    {{-- <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('lead_script');
    </script> --}}

@endsection
