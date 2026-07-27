@extends('admin.layout.layout')

@section('title', 'Admin | Scripts')

@section('admin-content')


    <style>
        .CodeMirror {
            font-family: monospace;
            height: 650px !important;
            color: #000;
            direction: ltr;
        }
    </style>

    <!-- ✅ CodeMirror Styles + JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/material-darker.min.css">
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <h1 class="fw-bold" style="color: #003C51;">Lead Script</h1>
                    <div class="d-flex">
                        <div class="d-flex">
                            <button type="submit" class="btn bg-gradient-3" data-toggle="modal" data-target="#addScript"
                                hidden>Add Script</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row my-5 fullInfo">
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold mb-0">Custom Lead Script (JS)</label>
                                    <button type="button" id="copyLeadScript" class="btn btn-sm btn-outline-primary">
                                        📋 Copy Script
                                    </button>
                                </div>
                                <textarea id="lead_script" name="lead_script" class="code-editor form-control" rows="15">
<script>
    (function() {
        const form = document.getElementById('lead-form');
        if (!form) return;

        const CRM_ENDPOINT = '{{ route('crm.leads.post') }}';
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
                brand_key: '{{ $brandKey ?? '' }}',
                channel: (f.namedItem('channel')?.value || '').trim() || 'web_form',
                url: location.origin + '/',
                brand_host: location.hostname,
                referrer: document.referrer || undefined,
                page_title: document.title,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                locale: navigator.language || undefined,
                session_id: getCid(),
                preferred_contact: (f.namedItem('preferred_contact')?.value || '').trim() ||
                    undefined,
                contact_time: (f.namedItem('contact_time')?.value || '').trim() || undefined,
                company: (f.namedItem('company')?.value || '').trim() || undefined,
                currency,
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
                const body = ct.includes('application/json') ? await res.json() : await res.text();

                if (!res.ok) {
                    console.error('CRM error', res.status, body);
                    alert(body?.message || `Failed (${res.status}). See console for details.`);
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
</script>
                                </textarea>
                                <small class="text-muted d-block mt-2">
                                    Use placeholders: <code>@{{ crm_endpoint }}</code>,
                                    <code>@{{ brand_key }}</code>, <code>@{{ brand_host }}</code>
                                </small>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const textarea = document.getElementById('lead_script');
                                const copyBtn = document.getElementById('copyLeadScript');

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

                                textarea.closest('form')?.addEventListener('submit', () => {
                                    textarea.value = editor.getValue();
                                });

                                // ✅ Copy Script Button
                                copyBtn.addEventListener('click', () => {
                                    const scriptContent = editor.getValue();
                                    navigator.clipboard.writeText(scriptContent).then(() => {
                                        copyBtn.textContent = '✅ Copied!';
                                        setTimeout(() => (copyBtn.textContent = '📋 Copy Script'), 2000);
                                    }).catch(err => {
                                        console.error('Copy failed', err);
                                        alert('Unable to copy. Please copy manually.');
                                    });
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
