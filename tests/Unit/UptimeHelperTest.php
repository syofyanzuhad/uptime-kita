<?php

use App\Helpers\UptimeHelper;
use App\Models\User;

beforeEach(function () {
    // Reset config to default
    config(['uptime.deployment_type' => 'self-hosted']);
});

it('correctly identifies SaaS deployment', function () {
    config(['uptime.deployment_type' => 'saas']);

    expect(UptimeHelper::isSaaS())->toBeTrue();
    expect(UptimeHelper::isSelfHosted())->toBeFalse();
    expect(UptimeHelper::getDeploymentType())->toBe('saas');
});

it('correctly identifies self-hosted deployment', function () {
    config(['uptime.deployment_type' => 'self-hosted']);

    expect(UptimeHelper::isSelfHosted())->toBeTrue();
    expect(UptimeHelper::isSaaS())->toBeFalse();
    expect(UptimeHelper::getDeploymentType())->toBe('self-hosted');
});

it('gets correct email daily limit for SaaS', function () {
    config([
        'uptime.deployment_type' => 'saas',
        'uptime.saas.email_daily_limit' => 10,
    ]);

    expect(UptimeHelper::getEmailDailyLimit())->toBe(10);
    expect(UptimeHelper::hasEmailLimit())->toBeTrue();
});

it('gets correct email daily limit for self-hosted', function () {
    config([
        'uptime.deployment_type' => 'self-hosted',
        'uptime.self_hosted.email_daily_limit' => 0,
    ]);

    expect(UptimeHelper::getEmailDailyLimit())->toBe(0);
    expect(UptimeHelper::hasEmailLimit())->toBeFalse();
});

it('gets configuration based on deployment type', function () {
    // Test SaaS config
    config([
        'uptime.deployment_type' => 'saas',
        'uptime.saas.max_monitors_per_user' => 5,
    ]);

    expect(UptimeHelper::getConfig('max_monitors_per_user'))->toBe(5);

    // Test self-hosted config
    config([
        'uptime.deployment_type' => 'self-hosted',
        'uptime.self_hosted.max_monitors_per_user' => 100,
    ]);

    expect(UptimeHelper::getConfig('max_monitors_per_user'))->toBe(100);
});

it('gets common configuration correctly', function () {
    config([
        'uptime.common.check_timeout' => 30,
    ]);

    expect(UptimeHelper::getConfig('check_timeout'))->toBe(30);
});

it('checks feature availability for SaaS', function () {
    config([
        'uptime.deployment_type' => 'saas',
        'uptime.saas.features' => [
            'api_access' => false,
            'custom_status_pages' => true,
        ],
    ]);

    expect(UptimeHelper::isFeatureEnabled('api_access'))->toBeFalse();
    expect(UptimeHelper::isFeatureEnabled('custom_status_pages'))->toBeTrue();
});

it('checks feature availability for self-hosted', function () {
    config([
        'uptime.deployment_type' => 'self-hosted',
        'uptime.self_hosted.features' => [
            'api_access' => true,
            'white_label' => true,
        ],
    ]);

    expect(UptimeHelper::isFeatureEnabled('api_access'))->toBeTrue();
    expect(UptimeHelper::isFeatureEnabled('white_label'))->toBeTrue();
});

it('gets user plan for SaaS with free plan', function () {
    config([
        'uptime.deployment_type' => 'saas',
        'uptime.plans.free.email_daily_limit' => 10,
        'uptime.plans.free.max_monitors' => 5,
    ]);

    $user = new User;
    $user->subscription_plan = 'free';

    expect(UptimeHelper::getUserPlan($user, 'email_daily_limit'))->toBe(10);
    expect(UptimeHelper::getUserPlan($user, 'max_monitors'))->toBe(5);
});

it('gets user plan for SaaS with pro plan', function () {
    config([
        'uptime.deployment_type' => 'saas',
        'uptime.plans.pro.email_daily_limit' => 100,
        'uptime.plans.pro.max_monitors' => 50,
    ]);

    $user = new User;
    $user->subscription_plan = 'pro';

    expect(UptimeHelper::getUserPlan($user, 'email_daily_limit'))->toBe(100);
    expect(UptimeHelper::getUserPlan($user, 'max_monitors'))->toBe(50);
});

it('returns unlimited for self-hosted user plan', function () {
    config([
        'uptime.deployment_type' => 'self-hosted',
        'uptime.self_hosted.email_daily_limit' => 0,
        'uptime.self_hosted.max_monitors_per_user' => 0,
    ]);

    $user = new User;

    expect(UptimeHelper::getUserPlan($user, 'email_daily_limit'))->toBe(0);
    expect(UptimeHelper::getUserPlan($user, 'max_monitors_per_user'))->toBe(0);
});

it('gets deployment info correctly', function () {
    config([
        'uptime.deployment_type' => 'saas',
        'uptime.saas.email_daily_limit' => 10,
        'uptime.saas.telegram_daily_limit' => 50,
        'uptime.saas.max_monitors_per_user' => 5,
        'uptime.saas.min_check_frequency' => 5,
        'uptime.saas.data_retention_days' => 30,
        'uptime.saas.features' => [
            'custom_status_pages' => false,
            'team_collaboration' => false,
            'api_access' => false,
            'export_data' => false,
            'white_label' => false,
        ],
    ]);

    $info = UptimeHelper::getDeploymentInfo();

    expect($info['type'])->toBe('saas');
    expect($info['is_saas'])->toBeTrue();
    expect($info['is_self_hosted'])->toBeFalse();
    expect($info['email_limit'])->toBe(10);
    expect($info['telegram_limit'])->toBe(50);
    expect($info['monitor_limit'])->toBe(5);
    expect($info['min_check_frequency'])->toBe(5);
    expect($info['data_retention_days'])->toBe(30);
    expect($info['features']['api_access'])->toBeFalse();
});

it('correctly checks limits existence', function () {
    // Test with limits
    config([
        'uptime.deployment_type' => 'saas',
        'uptime.saas.email_daily_limit' => 10,
        'uptime.saas.telegram_daily_limit' => 50,
        'uptime.saas.max_monitors_per_user' => 5,
    ]);

    expect(UptimeHelper::hasEmailLimit())->toBeTrue();
    expect(UptimeHelper::hasTelegramLimit())->toBeTrue();
    expect(UptimeHelper::hasMonitorLimit())->toBeTrue();

    // Test without limits (self-hosted)
    config([
        'uptime.deployment_type' => 'self-hosted',
        'uptime.self_hosted.email_daily_limit' => 0,
        'uptime.self_hosted.telegram_daily_limit' => 0,
        'uptime.self_hosted.max_monitors_per_user' => 0,
    ]);

    expect(UptimeHelper::hasEmailLimit())->toBeFalse();
    expect(UptimeHelper::hasTelegramLimit())->toBeFalse();
    expect(UptimeHelper::hasMonitorLimit())->toBeFalse();
});
