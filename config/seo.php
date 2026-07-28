<?php

return [

    'site_name' => 'Ledrix CRM',

    'default_title' => 'Ledrix CRM — Multi-Tenant Sales CRM Software',

    'default_description' => 'Ledrix is a multi-tenant sales CRM for agencies and closers. Capture leads, assign sellers, manage orders, payment links, and client portals — tenant-isolated from day one.',

    'default_keywords' => 'Ledrix, Ledrix CRM, CRM software, sales CRM, multi-tenant CRM, lead management CRM, agency CRM, pipeline CRM, SaaS CRM, customer relationship management',

    'twitter_handle' => '@ledrixcrm',

    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),

    'theme_color' => '#4338ca',

    'og_image' => 'front-assets/imgs/logo-ic.png',

    'front_logo' => 'front-assets/imgs/logo-ic.png',
    'front_favicon' => 'front-assets/imgs/fv-icon.png',
    'front_favicon_32' => 'front-assets/imgs/favicon-32.png',
    'front_apple_touch_icon' => 'front-assets/imgs/apple-touch-icon.png',

    'social' => [
        'facebook'  => env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/profile.php?id=100063861860966'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL', 'https://www.instagram.com/ledrixtech/'),
        'linkedin'  => env('SOCIAL_LINKEDIN_URL', 'https://www.linkedin.com/company/ledrix-technologies'),
    ],

    'organization' => [
        'name' => 'Ledrix',
        'legal_name' => 'Ledrix CRM',
        'url' => null,
        'logo' => 'front-assets/imgs/logo-ic.png',
        'email' => 'hello@ledrix.co',
        'founding_date' => '2024',
        'same_as' => array_values(array_filter([
            env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/ledrixcrm'),
            env('SOCIAL_INSTAGRAM_URL', 'https://www.instagram.com/ledrixcrm'),
            env('SOCIAL_LINKEDIN_URL', 'https://www.linkedin.com/in/zeeshan-asghar-500a40255/'),
        ])),
    ],

    'founder' => [
        'name' => 'Zeeshan Asghar',
        'job_title' => 'Founder & CEO',
        'linkedin' => 'https://www.linkedin.com/in/zeeshan-asghar-500a40255/',
        'photo' => 'front-assets/imgs/founder-lounge.png',
        'story' => [
            'origin' => 'While working with agencies and sales teams, Zeeshan Asghar noticed a familiar pattern: closers were drowning in spreadsheets, payment links lived in one tool, leads in another, and client updates in a third. Sellers did not need more features — they needed one workspace that matched how deals actually move.',
            'founding' => 'In 2024, he founded Ledrix to help revenue teams grow with structure instead of chaos — capturing leads, assigning sellers, closing orders, and collecting payments in a tenant-isolated CRM built for agencies from day one.',
            'today' => 'Ledrix has expanded into a crafted, not cobbled platform: multi-brand workspaces, seller and client portals, Stripe and PayPal flows, and automation-ready architecture. Led by Zeeshan, the team is building the practical sales operating system scaling agencies expect today — and the intelligent CRM they will need tomorrow.',
        ],
        'description' => 'Zeeshan Asghar founded Ledrix to build practical, scalable CRM technology for sales teams and agencies — combining pipeline discipline with modern SaaS architecture.',
    ],

    'sitemap' => [
        ['path' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['path' => '/features', 'priority' => '0.9', 'changefreq' => 'monthly'],
        ['path' => '/pricing', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['path' => '/about', 'priority' => '0.85', 'changefreq' => 'monthly'],
        ['path' => '/faq', 'priority' => '0.85', 'changefreq' => 'monthly'],
        ['path' => '/contact-us', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ],

    'robots_disallow' => [
        '/admin',
        '/seller',
        '/super-admin',
        '/client',
        '/compliance',
        '/upwork',
        '/sign-in',
        '/register',
        '/tenant-profile',
        '/verify-email',
        '/billing',
        '/pay/',
        '/api/',
    ],

    'faq' => [
        [
            'question' => 'What is Ledrix CRM?',
            'answer' => 'Ledrix CRM is a multi-tenant sales CRM built for agencies, closers, and revenue teams. It connects lead capture, seller assignment, orders, payment links, and client portals in one tenant-isolated workspace.',
        ],
        [
            'question' => 'Who is Ledrix CRM for?',
            'answer' => 'Ledrix is designed for digital agencies, sales teams, project managers, front sellers, and growing businesses that need structured pipelines, role-based panels, and payment workflows without juggling multiple tools.',
        ],
        [
            'question' => 'Is Ledrix CRM free to try?',
            'answer' => 'Yes. Ledrix offers a free trial on published plans — typically 14 days with full CRM access and no credit card required. Visit the pricing page to compare plans and start registration.',
        ],
        [
            'question' => 'How is Ledrix different from other CRM software?',
            'answer' => 'Ledrix is seller-first and agency-ready: multi-brand workspaces, dedicated seller and admin panels, Stripe and PayPal payment links, client portal, Upwork module, and tenant isolation from day one — not a generic contact database.',
        ],
        [
            'question' => 'Does Ledrix support multiple brands or teams?',
            'answer' => 'Yes. Each tenant workspace can run multiple brands, sellers, and clients with scoped data access. Admins see the full picture; sellers see their pipeline; clients get a secure portal.',
        ],
        [
            'question' => 'What payment gateways does Ledrix CRM support?',
            'answer' => 'Ledrix supports Stripe and PayPal payment links, milestone billing, and subscription billing for tenant plans. Payment status flows back into orders and seller performance reporting.',
        ],
        [
            'question' => 'Can I use Ledrix CRM for lead management only?',
            'answer' => 'Yes. Lead intake via API, webhooks, or manual entry is a core module. You can route leads to sellers, track assignments, and expand into orders and payments when ready.',
        ],
        [
            'question' => 'Is my data isolated on Ledrix?',
            'answer' => 'Every customer workspace is tenant-scoped. CRM records are isolated by tenant ID so your brands, leads, orders, and clients remain separate from other organizations on the platform.',
        ],
        [
            'question' => 'Who founded Ledrix?',
            'answer' => 'Ledrix was founded by Zeeshan Asghar, who leads product and platform direction with a focus on practical CRM tools for modern sales teams and agencies.',
        ],
        [
            'question' => 'How do I contact Ledrix for sales or support?',
            'answer' => 'Use the contact page at ledrix.co/contact-us for pricing questions, enterprise setup, or product demos. The team typically responds within one business day.',
        ],
    ],

    'pricing_faq' => [
        [
            'question' => 'How does the Ledrix CRM free trial work?',
            'answer' => 'Choose a plan and create your workspace. You get full CRM access for the trial period on your package. We verify your email before activating the trial — no payment is collected upfront.',
        ],
        [
            'question' => 'Do I need a credit card to start a Ledrix trial?',
            'answer' => 'No. You can start your free trial without entering card details. Billing is only required when you choose to continue after the trial ends.',
        ],
        [
            'question' => 'What happens after my Ledrix trial ends?',
            'answer' => 'Your tenant dashboard will prompt you to subscribe. Until then, CRM access may be limited based on subscription status. You can upgrade or change plans at any time from your workspace.',
        ],
        [
            'question' => 'How do I access the CRM after signing up?',
            'answer' => 'After email verification, sign in to your tenant dashboard and open the CRM admin panel. Your admin account is provisioned automatically with the same credentials you registered with.',
        ],
        [
            'question' => 'Is my Ledrix workspace isolated from other companies?',
            'answer' => 'Yes. Ledrix is multi-tenant SaaS — each workspace has its own tenant ID. Your leads, sellers, clients, and orders are scoped to your account only.',
        ],
        [
            'question' => 'Can I switch Ledrix CRM plans later?',
            'answer' => 'Yes. Contact support or use your tenant dashboard to move between plans. Limits and modules update according to your new package.',
        ],
        [
            'question' => 'Can I cancel my Ledrix subscription anytime?',
            'answer' => 'Yes. Cancel before renewal and you will not be charged for the next cycle. Your data retention policy applies after cancellation.',
        ],
        [
            'question' => 'What payment methods does Ledrix support?',
            'answer' => 'Stripe and PayPal are supported on eligible plans for tenant subscriptions and CRM payment links. Payment setup is completed after trial when you choose to subscribe.',
        ],
    ],

];
