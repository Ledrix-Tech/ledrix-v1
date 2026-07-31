@extends('sellers.layout.layout')

@section('title', 'Seller | Lead Script')

@section('sellers-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading d-flex justify-content-between">
                    <div>
                        <h1 class="fw-bold" style="color: #003C51;">Lead Script</h1>
                        @if ($brand ?? null)
                            <p class="text-muted mb-0">
                                Brand: <strong>{{ $brand->brand_name }}</strong>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="row my-4">
            <div class="col-lg-10">
                <div class="alert alert-info">
                    Paste this embed block on your brand website. It loads the dynamic Ledrix script and includes
                    an inline fallback if the script URL fails.
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <label class="form-label fw-bold">Embed snippet</label>
                        <textarea id="embedSnippet" class="form-control font-monospace mb-3" rows="6" readonly>{{ $scriptService->embedSnippetForBrand($brand) }}</textarea>
                        <button type="button" id="copyEmbed" class="btn btn-outline-primary">Copy embed</button>

                        <p class="small text-muted mb-2">
                            Your website form must use <code>id="lead-form"</code>.
                            Default fields: <code>name</code>, <code>email</code>, <code>phone</code>,
                            <code>service</code>, <code>message</code>.
                        </p>

                        <p class="small text-muted mb-0">
                            Script preview:
                            <a href="{{ $scriptService->scriptUrlForBrand($brand) }}" target="_blank">
                                {{ $scriptService->scriptUrlForBrand($brand) }}
                            </a>
                        </p>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold">Requirements</h5>
                        <ul class="mb-0">
                            <li>Tenant subscription must be active with API access enabled.</li>
                            <li>Brand domain must match the site where the form is installed.</li>
                            <li>If field names differ from defaults, ask your admin to update field mapping.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('copyEmbed')?.addEventListener('click', function () {
            var input = document.getElementById('embedSnippet');
            navigator.clipboard.writeText(input.value).then(function () {
                var btn = document.getElementById('copyEmbed');
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = 'Copy embed'; }, 2000);
            });
        });
    </script>
@endsection
