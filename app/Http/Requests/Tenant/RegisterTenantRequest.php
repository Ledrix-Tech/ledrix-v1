<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pkg_slug'        => ['required', 'string', Rule::exists('central.package_pricings', 'slug')->where('status', 'active')],
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', Rule::unique('central.tenants', 'email')],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'address'         => ['nullable', 'string', 'max:500'],
            'website'         => ['nullable', 'url', 'max:255'],
            'country'         => ['required', 'string', 'max:5'],
            'billing_name'    => ['required', 'string', 'max:255'],
            'billing_email'   => ['required', 'email', 'max:255'],
            'billing_phone'   => ['nullable', 'string', 'max:50'],
            'billing_address' => ['required', 'string', 'max:500'],
            'billing_cycle'   => ['nullable', Rule::in(['monthly', 'yearly'])],
            'referral_code'   => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'pkg_slug.exists' => 'The selected plan is not available.',
            'email.unique'    => 'An account with this email already exists.',
        ];
    }
}
