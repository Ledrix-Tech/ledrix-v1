<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paid subscription renewal reminders (days before end_date)
    |--------------------------------------------------------------------------
    |
    | The daily `tenants:process-subscriptions` command sends one email per
    | threshold while the membership is still active and not yet expired.
    |
    */
    'renewal_reminder_days' => array_map('intval', explode(',', env('SUBSCRIPTION_RENEWAL_REMINDER_DAYS', '7,3,1'))),

    /*
    |--------------------------------------------------------------------------
    | Early renewal window (days before end_date)
    |--------------------------------------------------------------------------
    |
    | Tenants with an active paid subscription can pay early from the billing
    | page when their end_date is within this many days.
    |
    */
    'early_renew_days' => (int) env('SUBSCRIPTION_EARLY_RENEW_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Grace period after end_date (past_due → expired)
    |--------------------------------------------------------------------------
    */
    'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', env('JAZZCASH_GRACE_DAYS', 7)),

];
