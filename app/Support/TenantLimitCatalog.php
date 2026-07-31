<?php

namespace App\Support;

/**
 * Plan limit keys (match package_pricings + tenant_limit_overrides).
 */
final class TenantLimitCatalog
{
    /** @var array<string, array{label: string, group: string, description: string}> */
    public const LIMITS = [
        'max_brands' => [
            'label'       => 'Brands',
            'group'       => 'Workspace',
            'description' => 'Maximum brands / domains per tenant.',
        ],
        'max_sellers' => [
            'label'       => 'Sellers',
            'group'       => 'Team',
            'description' => 'Maximum seller accounts (front sellers + project managers).',
        ],
        'max_admins' => [
            'label'       => 'Admins',
            'group'       => 'Team',
            'description' => 'Maximum CRM admin users.',
        ],
        'max_clients' => [
            'label'       => 'Clients',
            'group'       => 'Workspace',
            'description' => 'Maximum client records.',
        ],
        'max_leads_per_month' => [
            'label'       => 'Leads / month',
            'group'       => 'Usage',
            'description' => 'Monthly lead intake cap (resets each calendar month).',
        ],
        'max_orders' => [
            'label'       => 'Orders',
            'group'       => 'Usage',
            'description' => 'Maximum orders in the pipeline.',
        ],
        'max_payment_links' => [
            'label'       => 'Payment links',
            'group'       => 'Usage',
            'description' => 'Maximum payment links generated.',
        ],
        'max_account_keys' => [
            'label'       => 'Account keys',
            'group'       => 'Integrations',
            'description' => 'Stripe/PayPal keys per brand.',
        ],
        'max_projects' => [
            'label'       => 'Projects',
            'group'       => 'Workspace',
            'description' => 'Maximum project records.',
        ],
        'max_storage_mb' => [
            'label'       => 'Storage (MB)',
            'group'       => 'Usage',
            'description' => 'File storage allowance in megabytes.',
        ],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::LIMITS);
    }

    public static function label(string $key): string
    {
        return self::LIMITS[$key]['label'] ?? $key;
    }

    public static function formatValue(?int $value): string
    {
        if ($value === null) {
            return '—';
        }

        return $value === -1 ? 'Unlimited' : (string) $value;
    }
}
