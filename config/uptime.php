<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Type
    |--------------------------------------------------------------------------
    |
    | This value determines whether the application is running as a
    | cloud SaaS or self-hosted deployment. This affects various
    | features and limitations.
    |
    | Options: 'saas' or 'self-hosted'
    |
    */
    'deployment_type' => env('UPTIME_DEPLOYMENT_TYPE', 'self-hosted'),

    /*
    |--------------------------------------------------------------------------
    | SaaS Configuration
    |--------------------------------------------------------------------------
    |
    | These settings apply only when deployment_type is 'saas'
    |
    */
    'saas' => [
        // Email notification limits
        'email_daily_limit' => env('UPTIME_SAAS_EMAIL_DAILY_LIMIT', 10),

        // Monitor limits per user
        'max_monitors_per_user' => env('UPTIME_SAAS_MAX_MONITORS', 5),

        // Check frequency limits (in minutes)
        'min_check_frequency' => env('UPTIME_SAAS_MIN_CHECK_FREQUENCY', 5),

        // Data retention (in days)
        'data_retention_days' => env('UPTIME_SAAS_DATA_RETENTION', 30),

        // Features
        'features' => [
            'custom_status_pages' => env('UPTIME_SAAS_CUSTOM_STATUS_PAGES', false),
            'team_collaboration' => env('UPTIME_SAAS_TEAM_COLLABORATION', false),
            'api_access' => env('UPTIME_SAAS_API_ACCESS', false),
            'export_data' => env('UPTIME_SAAS_EXPORT_DATA', false),
            'white_label' => env('UPTIME_SAAS_WHITE_LABEL', false),
        ],

        // Telegram limits
        'telegram_daily_limit' => env('UPTIME_SAAS_TELEGRAM_DAILY_LIMIT', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Self-Hosted Configuration
    |--------------------------------------------------------------------------
    |
    | These settings apply only when deployment_type is 'self-hosted'
    |
    */
    'self_hosted' => [
        // Email notification limits (0 = unlimited)
        'email_daily_limit' => env('UPTIME_SELF_EMAIL_DAILY_LIMIT', 0),

        // Monitor limits per user (0 = unlimited)
        'max_monitors_per_user' => env('UPTIME_SELF_MAX_MONITORS', 0),

        // Check frequency limits (in minutes)
        'min_check_frequency' => env('UPTIME_SELF_MIN_CHECK_FREQUENCY', 1),

        // Data retention (in days, 0 = unlimited)
        'data_retention_days' => env('UPTIME_SELF_DATA_RETENTION', 0),

        // Features (all enabled by default for self-hosted)
        'features' => [
            'custom_status_pages' => env('UPTIME_SELF_CUSTOM_STATUS_PAGES', true),
            'team_collaboration' => env('UPTIME_SELF_TEAM_COLLABORATION', true),
            'api_access' => env('UPTIME_SELF_API_ACCESS', true),
            'export_data' => env('UPTIME_SELF_EXPORT_DATA', true),
            'white_label' => env('UPTIME_SELF_WHITE_LABEL', true),
        ],

        // Telegram limits (0 = unlimited)
        'telegram_daily_limit' => env('UPTIME_SELF_TELEGRAM_DAILY_LIMIT', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Settings
    |--------------------------------------------------------------------------
    |
    | These settings apply to both SaaS and self-hosted deployments
    |
    */
    'common' => [
        // Default check timeout (in seconds)
        'check_timeout' => env('UPTIME_CHECK_TIMEOUT', 30),

        // Default check method
        'default_check_method' => env('UPTIME_DEFAULT_CHECK_METHOD', 'GET'),

        // Enable public status pages
        'enable_public_status_pages' => env('UPTIME_ENABLE_PUBLIC_STATUS', true),

        // Maintenance mode
        'maintenance_mode' => env('UPTIME_MAINTENANCE_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plans Configuration (for SaaS)
    |--------------------------------------------------------------------------
    |
    | Define different subscription plans for SaaS deployment
    |
    */
    'plans' => [
        'free' => [
            'name' => 'Free',
            'max_monitors' => 5,
            'min_check_frequency' => 10, // minutes
            'email_daily_limit' => 10,
            'telegram_daily_limit' => 50,
            'data_retention_days' => 7,
            'features' => [
                'custom_status_pages' => false,
                'team_collaboration' => false,
                'api_access' => false,
                'export_data' => false,
                'white_label' => false,
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'max_monitors' => 50,
            'min_check_frequency' => 1, // minutes
            'email_daily_limit' => 100,
            'telegram_daily_limit' => 500,
            'data_retention_days' => 90,
            'features' => [
                'custom_status_pages' => true,
                'team_collaboration' => true,
                'api_access' => true,
                'export_data' => true,
                'white_label' => false,
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'max_monitors' => 0, // unlimited
            'min_check_frequency' => 1, // minutes
            'email_daily_limit' => 0, // unlimited
            'telegram_daily_limit' => 0, // unlimited
            'data_retention_days' => 365,
            'features' => [
                'custom_status_pages' => true,
                'team_collaboration' => true,
                'api_access' => true,
                'export_data' => true,
                'white_label' => true,
            ],
        ],
    ],
];
