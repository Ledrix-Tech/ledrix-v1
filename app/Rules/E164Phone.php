<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class E164Phone implements ValidationRule
{
    public function __construct(
        private readonly bool $required = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = trim((string) $value);

        if ($phone === '') {
            if ($this->required) {
                $fail('Please enter a valid phone number with country code.');
            }

            return;
        }

        // intl-tel-input submits E.164 e.g. +923001234567
        if (! preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            $fail('Enter a valid phone number with country code (e.g. +92 300 1234567).');
        }
    }
}
