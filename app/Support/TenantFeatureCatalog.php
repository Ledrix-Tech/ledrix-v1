<?php

namespace App\Support;

/**
 * Canonical tenant feature keys (match package_pricings + tenant_feature_overrides columns).
 */
final class TenantFeatureCatalog
{
    /** @var array<string, array{key: string, column: string, label: string, group: string, description: string}> */
    public const FEATURES = [
        'feature_ppc_module' => [
            'key'         => 'ppc_module',
            'column'      => 'feature_ppc_module',
            'label'       => 'PPC Module',
            'group'       => 'Modules',
            'description' => 'Core PPC CRM (leads, orders, brands).',
        ],
        'feature_upwork_module' => [
            'key'         => 'upwork_module',
            'column'      => 'feature_upwork_module',
            'label'       => 'Upwork Module',
            'group'       => 'Modules',
            'description' => 'Separate Upwork orders and payment flow.',
        ],
        'feature_stripe' => [
            'key'         => 'stripe',
            'column'      => 'feature_stripe',
            'label'       => 'Stripe Payments',
            'group'       => 'Payments',
            'description' => 'Allow Stripe checkout and account keys.',
        ],
        'feature_paypal' => [
            'key'         => 'paypal',
            'column'      => 'feature_paypal',
            'label'       => 'PayPal Payments',
            'group'       => 'Payments',
            'description' => 'Allow PayPal checkout and account keys.',
        ],
        'feature_webhooks' => [
            'key'         => 'webhooks',
            'column'      => 'feature_webhooks',
            'label'       => 'Payment Webhooks',
            'group'       => 'Payments',
            'description' => 'Stripe/PayPal webhook processing for missed payments.',
        ],
        'feature_milestone_payments' => [
            'key'         => 'milestone_payments',
            'column'      => 'feature_milestone_payments',
            'label'       => 'Milestone Payments',
            'group'       => 'Payments',
            'description' => 'Installment / milestone payment links.',
        ],
        'feature_lead_prediction' => [
            'key'         => 'lead_prediction',
            'column'      => 'feature_lead_prediction',
            'label'       => 'Lead Classification',
            'group'       => 'Leads & AI',
            'description' => 'AI lead scoring and classification on intake.',
        ],
        'feature_client_portal' => [
            'key'         => 'client_portal',
            'column'      => 'feature_client_portal',
            'label'       => 'Client Portal',
            'group'       => 'Portal',
            'description' => 'Client login, invoices, and tickets.',
        ],
        'feature_support_tickets' => [
            'key'         => 'support_tickets',
            'column'      => 'feature_support_tickets',
            'label'       => 'Support Tickets',
            'group'       => 'Portal',
            'description' => 'Order-linked client support tickets.',
        ],
        'feature_chargeback_tracking' => [
            'key'         => 'chargeback_tracking',
            'column'      => 'feature_chargeback_tracking',
            'label'       => 'Chargeback Tracking',
            'group'       => 'Payments',
            'description' => 'Dispute and refund webhook handling.',
        ],
        'feature_dual_invoicing' => [
            'key'         => 'dual_invoicing',
            'column'      => 'feature_dual_invoicing',
            'label'       => 'Dual Invoicing',
            'group'       => 'Payments',
            'description' => 'Advanced invoicing options.',
        ],
        'feature_seller_leaderboard' => [
            'key'         => 'seller_leaderboard',
            'column'      => 'feature_seller_leaderboard',
            'label'       => 'Seller Leaderboard',
            'group'       => 'Team',
            'description' => 'Seller performance rankings.',
        ],
        'feature_performance_bonus' => [
            'key'         => 'performance_bonus',
            'column'      => 'feature_performance_bonus',
            'label'       => 'Performance Bonus',
            'group'       => 'Team',
            'description' => 'Bonus tracking for sellers.',
        ],
        'feature_projects' => [
            'key'         => 'projects',
            'column'      => 'feature_projects',
            'label'       => 'Projects',
            'group'       => 'Portal',
            'description' => 'Project management module.',
        ],
        'feature_api_access' => [
            'key'         => 'api_access',
            'column'      => 'feature_api_access',
            'label'       => 'API Access',
            'group'       => 'Integrations',
            'description' => 'Public lead/branding API endpoints.',
        ],
        'feature_custom_domain' => [
            'key'         => 'custom_domain',
            'column'      => 'feature_custom_domain',
            'label'       => 'Custom Domain',
            'group'       => 'Integrations',
            'description' => 'Tenant custom domain support.',
        ],
        'feature_white_label' => [
            'key'         => 'white_label',
            'column'      => 'feature_white_label',
            'label'       => 'White Label',
            'group'       => 'Integrations',
            'description' => 'Remove Ledrix branding.',
        ],
    ];

    /** @return list<string> */
    public static function columns(): array
    {
        return array_keys(self::FEATURES);
    }

    /** @return array<string, array<string, array{key: string, column: string, label: string, group: string, description: string}>> */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::FEATURES as $feature) {
            $groups[$feature['group']][$feature['column']] = $feature;
        }

        return $groups;
    }
}
