<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

class UptimeHelper
{
    /**
     * Check if the application is running as SaaS
     */
    public static function isSaaS(): bool
    {
        return config('uptime.deployment_type') === 'saas';
    }

    /**
     * Check if the application is self-hosted
     */
    public static function isSelfHosted(): bool
    {
        return config('uptime.deployment_type') === 'self-hosted';
    }

    /**
     * Get the deployment type
     */
    public static function getDeploymentType(): string
    {
        return config('uptime.deployment_type', 'self-hosted');
    }

    /**
     * Get configuration based on deployment type
     */
    public static function getConfig(string $key, $default = null)
    {
        $deploymentType = self::getDeploymentType();
        // Convert hyphens to underscores for config key
        $deploymentConfigKey = str_replace('-', '_', $deploymentType);
        $configKey = "uptime.{$deploymentConfigKey}.{$key}";

        // First check deployment-specific config
        if (Config::has($configKey)) {
            return config($configKey, $default);
        }

        // Then check common config
        $commonKey = "uptime.common.{$key}";
        if (Config::has($commonKey)) {
            return config($commonKey, $default);
        }

        return $default;
    }

    /**
     * Get email daily limit based on deployment type
     */
    public static function getEmailDailyLimit(): int
    {
        return (int) self::getConfig('email_daily_limit', 0);
    }

    /**
     * Get Telegram daily limit based on deployment type
     */
    public static function getTelegramDailyLimit(): int
    {
        return (int) self::getConfig('telegram_daily_limit', 0);
    }

    /**
     * Get max monitors per user based on deployment type
     */
    public static function getMaxMonitorsPerUser(): int
    {
        return (int) self::getConfig('max_monitors_per_user', 0);
    }

    /**
     * Get minimum check frequency based on deployment type
     */
    public static function getMinCheckFrequency(): int
    {
        return (int) self::getConfig('min_check_frequency', 1);
    }

    /**
     * Get data retention days based on deployment type
     */
    public static function getDataRetentionDays(): int
    {
        return (int) self::getConfig('data_retention_days', 0);
    }

    /**
     * Check if a feature is enabled based on deployment type
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return (bool) self::getConfig("features.{$feature}", false);
    }

    /**
     * Get user plan configuration (for SaaS)
     */
    public static function getUserPlan($user, ?string $key = null)
    {
        if (self::isSelfHosted()) {
            // For self-hosted, return unlimited/all features
            if ($key) {
                return self::getConfig($key, 0);
            }

            return config('uptime.self_hosted');
        }

        // For SaaS, get user's plan
        $planName = $user->subscription_plan ?? 'free';
        $planConfig = config("uptime.plans.{$planName}", config('uptime.plans.free'));

        if ($key) {
            return data_get($planConfig, $key);
        }

        return $planConfig;
    }

    /**
     * Check if email notifications are limited
     */
    public static function hasEmailLimit(): bool
    {
        $limit = self::getEmailDailyLimit();

        return $limit > 0;
    }

    /**
     * Check if Telegram notifications are limited
     */
    public static function hasTelegramLimit(): bool
    {
        $limit = self::getTelegramDailyLimit();

        return $limit > 0;
    }

    /**
     * Check if monitors are limited
     */
    public static function hasMonitorLimit(): bool
    {
        $limit = self::getMaxMonitorsPerUser();

        return $limit > 0;
    }

    /**
     * Get deployment info for display
     */
    public static function getDeploymentInfo(): array
    {
        $deploymentType = self::getDeploymentType();

        return [
            'type' => $deploymentType,
            'is_saas' => self::isSaaS(),
            'is_self_hosted' => self::isSelfHosted(),
            'email_limit' => self::getEmailDailyLimit(),
            'telegram_limit' => self::getTelegramDailyLimit(),
            'monitor_limit' => self::getMaxMonitorsPerUser(),
            'min_check_frequency' => self::getMinCheckFrequency(),
            'data_retention_days' => self::getDataRetentionDays(),
            'features' => [
                'custom_status_pages' => self::isFeatureEnabled('custom_status_pages'),
                'team_collaboration' => self::isFeatureEnabled('team_collaboration'),
                'api_access' => self::isFeatureEnabled('api_access'),
                'export_data' => self::isFeatureEnabled('export_data'),
                'white_label' => self::isFeatureEnabled('white_label'),
            ],
        ];
    }
}
