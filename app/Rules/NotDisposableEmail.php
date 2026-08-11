<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDisposableEmail implements ValidationRule
{
    /** Common disposable / temporary mailbox providers. */
    private const BLOCKED_DOMAINS = [
        'mailinator.com',
        'guerrillamail.com',
        'guerrillamail.net',
        '10minutemail.com',
        'tempmail.com',
        'temp-mail.org',
        'throwawaymail.com',
        'yopmail.com',
        'trashmail.com',
        'getnada.com',
        'sharklasers.com',
        'maildrop.cc',
        'dispostable.com',
        'fakeinbox.com',
        'mailnesia.com',
        'mintemail.com',
        'moakt.com',
        'emailondeck.com',
        'discard.email',
        'mailcatch.com',
        'tmpmail.org',
        'tmpmail.net',
        'trash-mail.com',
        'wegwerfmail.de',
        'spamgourmet.com',
        'mailnull.com',
        'jetable.org',
        'example.com',
        'example.org',
        'example.net',
        'test.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        $domain = substr(strrchr($email, '@') ?: '', 1);

        if ($domain === '') {
            $fail('Please enter a valid work email address.');

            return;
        }

        if (in_array($domain, self::BLOCKED_DOMAINS, true)) {
            $fail('Disposable or temporary email addresses are not allowed. Use a work email.');

            return;
        }

        if (preg_match('/^(temp|tmp|fake|trash|spam|discard|throwaway)/i', $domain)) {
            $fail('Disposable or temporary email addresses are not allowed. Use a work email.');
        }
    }
}
