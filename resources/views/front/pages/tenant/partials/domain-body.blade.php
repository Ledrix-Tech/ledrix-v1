<div class="container py-4" style="max-width: 760px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Custom domain &amp; branding</h4>
            <p class="text-muted mb-0 small">{{ $tenant->name }}</p>
        </div>
        <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($hasCustomDomain)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header">Custom domain</div>
            <div class="card-body">
                <form method="POST" action="{{ org_route('domain.update') }}" class="mb-4">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="custom_domain">Hostname</label>
                        <input id="custom_domain" type="text" name="custom_domain"
                            class="form-control @error('custom_domain') is-invalid @enderror"
                            value="{{ old('custom_domain', $tenant->custom_domain) }}"
                            placeholder="crm.youragency.com">
                        @error('custom_domain')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Leave blank and save to remove the custom domain.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save domain</button>
                </form>

                @if ($tenant->custom_domain)
                    <p class="mb-2">
                        Status:
                        @if ($tenant->custom_domain_verified)
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending DNS</span>
                        @endif
                    </p>

                    <div class="border rounded p-3 bg-light mb-3">
                        <p class="small mb-2"><strong>DNS instructions</strong></p>
                        <ol class="small mb-0">
                            <li>
                                Add a <strong>TXT</strong> record on
                                <code>{{ $txtHost }}</code>
                                with value
                                <code>{{ $verifyToken }}</code>
                            </li>
                            <li class="mt-2">
                                Or add a <strong>CNAME</strong> from
                                <code>{{ $tenant->custom_domain }}</code>
                                to
                                <code>{{ $platformHost }}</code>
                            </li>
                        </ol>
                    </div>

                    @unless ($tenant->custom_domain_verified)
                        <form method="POST" action="{{ org_route('domain.verify') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">Verify DNS</button>
                        </form>
                    @endunless
                @endif
            </div>
        </div>
    @endif

    @if ($hasWhiteLabel)
        <div class="card shadow-sm border-0">
            <div class="card-header">White-label logo</div>
            <div class="card-body">
                <p class="text-muted small">Replace Ledrix branding in the CRM chrome with your logo.</p>
                @if ($tenant->logo)
                    <div class="mb-3">
                        <img src="{{ asset('storage/'.$tenant->logo) }}" alt="Logo" style="max-height: 48px;">
                    </div>
                @endif
                <form method="POST" action="{{ org_route('domain.branding') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                    @csrf
                    <div>
                        <label class="form-label" for="logo">Logo image</label>
                        <input id="logo" type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
                @if ($tenant->logo)
                    <form method="POST" action="{{ org_route('domain.branding') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="remove_logo" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove logo</button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
