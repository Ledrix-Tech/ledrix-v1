<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Leads Portal</title>
    <style>
        body,
        html {
            height: 100%;
        }

        .form-wrapper {
            height: 100vh;
        }
    </style>

    <script src="http://127.0.0.1:8000/api/lead-script/nexus.js" defer></script>
</head>



<body>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
        integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/js/toastr.js"></script>

    <script>
        $(document).ready(function() {
            toastr.options.timeOut = 10000;

            @if (Session::has('success'))
                toastr.success('{{ Session::get('success') }}');
            @endif

            @if (Session::has('info'))
                toastr.info('{{ Session::get('info') }}');
            @endif

            @if (Session::has('warning'))
                toastr.warning('{{ Session::get('warning') }}');
            @endif

            @if (Session::has('error'))
                toastr.error('{{ Session::get('error') }}');
            @endif
        });
    </script>

    <div class="container d-flex justify-content-center align-items-center form-wrapper">
        <div class="col-md-6 bg-white shadow p-5 rounded">
            <h3 class="mb-4 text-center">Nexus Lead</h3>
            <form method="GET" id="lead-form">
                @csrf
                <input type="hidden" name="url" value="{{ url()->current() }}" id="currentUrl">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" id="name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" id="email" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" id="phone" required
                        placeholder="000 *** ****">
                </div>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="field mb-3">
                        <label for="service" class="form-label">Packages</label>
                        <select name="service" required class="form-control" id="service">
                            <option value="" disabled selected>Select a Service*</option>
                            <option value="Logo Design">Logo Design</option>
                            <option value="Logo Animation">Logo Animation</option>
                            <option value="Video Animation">Video Animation</option>
                            <option value="Content Development">Content Development</option>
                            <option value="Website Design & Development">Website Design & Development</option>
                            <option value="Search Engine Optimization">Search Engine Optimization</option>
                            <option value="Social Media Marketing">Social Media Marketing</option>
                            <option value="Merchandise">Merchandise</option>
                            <option value="Packaging & Labels">Packaging & Labels</option>
                            <option value="Marketing Collateral">Marketing Collateral</option>
                            <option value="Domain & Hosting">Domain & Hosting</option>
                            <option value="Online Reputation Management">Online Reputation Management</option>
                            <option value="Ebook Design & Formatting Brief">Ebook Design & Formatting Brief</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" hidden>
                    <div class="field mb-3">
                        <label for="provider">Payment Provider</label>
                        <select name="provider" class="form-control" id="provider">
                            <option value="">-- select method --</option>
                            <option value="stripe">Stripe</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3" hidden>
                    <label for="price" class="form-label">Package Price $</label>
                    <input type="number" name="price" class="form-control" id="price" placeholder="e.g. ***">
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" name="message" id="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="lead-submit">Submit</button>
            </form>
        </div>
    </div>

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    <script>
        (function() {
            const form = document.getElementById('lead-form');
            if (!form) return;

            const CRM_ENDPOINT = 'http://127.0.0.1:8000/api/crm-lead-post';
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
                    brand_key: '',
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


</body>

</html>
