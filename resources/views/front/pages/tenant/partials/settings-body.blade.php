<div class="container py-4" style="max-width: 720px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Organization settings</h4>
            <p class="text-muted mb-0 small">Update company profile and billing contact details</p>
        </div>
        <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ org_route('settings.update') }}" class="card shadow-sm border-0">
        @csrf
        @method('PUT')
        <div class="card-body">
            <h6 class="mb-3">Company</h6>
            <div class="mb-3">
                <label class="form-label" for="name">Organization name</label>
                <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $tenant->name) }}" required maxlength="255">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone</label>
                    <div class="auth-phone-field">
                        <input id="phone" type="tel" name="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $tenant->phone) }}"
                            data-phone-input data-phone-sync-country="1"
                            data-initial-country="{{ strtolower(old('country', $tenant->country ?: 'PK')) }}"
                            maxlength="20" autocomplete="tel" placeholder="Phone number">
                    </div>
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="country">Country (ISO)</label>
                    <select id="country" name="country" class="form-select @error('country') is-invalid @enderror">
                        <option value="">Select country</option>
                        @foreach (['PK' => 'Pakistan', 'US' => 'United States', 'GB' => 'United Kingdom', 'IN' => 'India', 'AE' => 'United Arab Emirates', 'CA' => 'Canada', 'SA' => 'Saudi Arabia'] as $code => $label)
                            <option value="{{ $code }}" @selected(old('country', $tenant->country) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="website">Website</label>
                <input id="website" type="url" name="website" class="form-control @error('website') is-invalid @enderror"
                    value="{{ old('website', $tenant->website) }}" maxlength="255" placeholder="https://">
                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <h6 class="mb-3">Billing contact</h6>
            <div class="mb-3">
                <label class="form-label" for="billing_name">Billing name</label>
                <input id="billing_name" type="text" name="billing_name"
                    class="form-control @error('billing_name') is-invalid @enderror"
                    value="{{ old('billing_name', $tenant->billing_name) }}" maxlength="255">
                @error('billing_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="billing_email">Billing email</label>
                    <input id="billing_email" type="email" name="billing_email"
                        class="form-control @error('billing_email') is-invalid @enderror"
                        value="{{ old('billing_email', $tenant->billing_email) }}" maxlength="255">
                    @error('billing_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="billing_phone">Billing phone</label>
                    <div class="auth-phone-field">
                        <input id="billing_phone" type="tel" name="billing_phone"
                            class="form-control @error('billing_phone') is-invalid @enderror"
                            value="{{ old('billing_phone', $tenant->billing_phone) }}"
                            data-phone-input
                            data-initial-country="{{ strtolower(old('country', $tenant->country ?: 'PK')) }}"
                            maxlength="20" autocomplete="tel" placeholder="Billing phone">
                    </div>
                    @error('billing_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="billing_address">Billing address</label>
                <textarea id="billing_address" name="billing_address" rows="3"
                    class="form-control @error('billing_address') is-invalid @enderror"
                    maxlength="1000">{{ old('billing_address', $tenant->billing_address) }}</textarea>
                @error('billing_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>
</div>

@push('styles')
    @include('front.includes.phone-input-styles')
    <style>
        .auth-phone-field .iti { width: 100%; }
        .auth-phone-field .iti__country-list { z-index: 20; }
    </style>
@endpush
@push('scripts')
    @include('front.includes.phone-input-assets')
@endpush
