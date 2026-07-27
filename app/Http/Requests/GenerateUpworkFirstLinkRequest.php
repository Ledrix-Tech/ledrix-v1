<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\PaymentProvider;

class GenerateUpworkFirstLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = auth('admin')->user();
        return $admin && $admin->role === 'up_admin';
    }

    public function rules(): array
    {
        return [
            'client_name'      => ['required', 'string', 'max:255'],
            'client_email'     => ['required', 'email', 'max:255'],
            'client_phone'     => ['nullable', 'string', 'max:50'],
            'brandId'          => ['required', 'integer', 'exists:brands,id'],
            'service'          => ['required', 'string', 'max:255'],
            'currency'         => ['required', 'string', 'size:3'],
            'unit_amount'      => ['required', 'numeric', 'gt:0'],
            'payable_amount'   => ['required', 'numeric', 'gt:0'],
            'sell_type'        => ['required', Rule::in(['front', 'upsell'])],
            'provider'         => ['required', Rule::in(PaymentProvider::upworkAllowed())],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $unitAmount = (float) $this->input('unit_amount');
            $payableAmount = (float) $this->input('payable_amount');

            if ($payableAmount > $unitAmount) {
                $validator->errors()->add('payable_amount', 'Payable amount cannot exceed total amount.');
            }
        });
    }
}
