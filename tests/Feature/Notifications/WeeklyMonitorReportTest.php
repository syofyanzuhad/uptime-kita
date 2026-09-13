<?php

use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\WeeklyMonitorReport;

test('notification delivers via mail and active notification channels', function () {
    $user = User::factory()->create(['email' => 'alice@example.com']);

    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'telegram',
        'destination' => '987654321',
        'is_enabled' => true,
    ]);

    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'slack',
        'destination' => 'https://hooks.slack.com/services/xxx',
        'is_enabled' => true,
    ]);

    NotificationChannel::create([
        'user_id' => $user->id,
        'type' => 'email',
        'destination' => 'alice2@example.com',
        'is_enabled' => false, // disabled
    ]);

    $notification = new WeeklyMonitorReport(collect([]), []);

    $via = $notification->via($user);

    expect($via)->toContain('mail');
    expect($via)->toContain('telegram');
    expect($via)->toContain('slack');
});

test('notification renders correct uptime percentage and trend arrow in email', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $summaries = collect([
        [
            'monitor_id' => 101,
            'display_name' => 'API Production',
            'url' => 'https://api.example.com',
            'uptime_7d' => 99.85,
            'uptime_7d_prev' => 98.50, // improved -> trend up ↑
            'avg_response_ms' => 120,
            'incidents_7d' => 1,
            'total_downtime_mins' => 15,
            'is_up_now' => true,
            'monitor_url' => 'http://localhost/monitors/101',
        ],
        [
            'monitor_id' => 102,
            'display_name' => 'Web App',
            'url' => 'https://app.example.com',
            'uptime_7d' => 95.20,
            'uptime_7d_prev' => 99.90, // dropped -> trend down ↓
            'avg_response_ms' => 340,
            'incidents_7d' => 3,
            'total_downtime_mins' => 120,
            'is_up_now' => false,
            'monitor_url' => 'http://localhost/monitors/102',
        ],
    ]);

    $fleetTotals = [
        'total_monitors' => 2,
        'up_monitors' => 1,
        'down_monitors' => 1,
        'average_uptime' => 97.52,
        'total_downtime_mins' => 135,
        'best_performer' => $summaries[0],
        'worst_performer' => $summaries[1],
    ];

    $notification = new WeeklyMonitorReport($summaries, $fleetTotals, 'Week of Sep 07 – Sep 13, 2026');

    $mail = $notification->toMail($user);
    $html = (string) $mail->render();

    // Check header and greeting
    expect($mail->subject)->toContain('Your Weekly Uptime Report — Week of Sep 07 – Sep 13, 2026');
    expect($html)->toContain('John Doe');

    // Check fleet stats
    expect($html)->toContain('1 / 2 Up');
    expect($html)->toContain('97.52%');
    expect($html)->toContain('135m');

    // Check monitor rows and uptime %
    expect($html)->toContain('API Production');
    expect($html)->toContain('99.85%');
    expect($html)->toContain('Web App');
    expect($html)->toContain('95.20%');

    // Check trend indicators
    expect($html)->toContain('↑');
    expect($html)->toContain('↓');

    // Check callout boxes
    expect($html)->toContain('Best performer of the week');
    expect($html)->toContain('Worst performer of the week');
    expect($html)->toContain('120 mins');

    // Check unsubscribe link
    expect($html)->toContain('settings/weekly-report/unsubscribe');
});

test('telegram representation includes fleet stats and markdown formatting', function () {
    $user = User::factory()->create();

    $summaries = collect([
        [
            'monitor_id' => 1,
            'display_name' => 'Main Site',
            'url' => 'https://example.com',
            'uptime_7d' => 100.0,
            'uptime_7d_prev' => 100.0,
            'avg_response_ms' => 85,
            'incidents_7d' => 0,
            'total_downtime_mins' => 0,
            'is_up_now' => true,
            'monitor_url' => 'http://localhost/monitors/1',
        ],
    ]);

    $notification = new WeeklyMonitorReport($summaries);

    $telegram = $notification->toTelegram($user);

    $payload = $telegram->toArray();

    expect($payload['text'])->toContain('Weekly Uptime Report');
    expect($payload['text'])->toContain('Main Site');
    expect($payload['text'])->toContain('100.00%');
});
