<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\PaymentProvider;

class GenerateUpworkInstallmentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = auth('admin')->user();
        return $admin && $admin->role === 'up_admin';
    }

    public function rules(): array
    {
        return [
            'provider'         => ['required', Rule::in(PaymentProvider::upworkAllowed())],
            'payable_amount'   => ['required', 'numeric', 'gt:0'],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }
}
