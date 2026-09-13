<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

test('unsubscribe signed URL disables the weekly report', function () {
    $user = User::factory()->create([
        'weekly_report_enabled' => true,
    ]);

    $signedUrl = URL::signedRoute('settings.weekly-report.unsubscribe', ['user' => $user->id]);

    $response = $this->get($signedUrl);

    $response->assertOk();
    $response->assertViewIs('emails.weekly-report-unsubscribed');

    $user->refresh();
    expect($user->weekly_report_enabled)->toBeFalse();
});

test('unsigned or manipulated unsubscribe URL returns 403 forbidden', function () {
    $user = User::factory()->create([
        'weekly_report_enabled' => true,
    ]);

    $unsignedUrl = route('settings.weekly-report.unsubscribe', ['user' => $user->id]);

    $response = $this->get($unsignedUrl);

    $response->assertForbidden();

    $user->refresh();
    expect($user->weekly_report_enabled)->toBeTrue();
});

test('authenticated user can update weekly report preferences via patch', function () {
    $user = User::factory()->create([
        'weekly_report_enabled' => true,
        'weekly_report_timezone' => 'UTC',
    ]);

    $response = $this->actingAs($user)
        ->from('/settings/notifications')
        ->patch('/settings/weekly-report', [
            'weekly_report_enabled' => false,
            'weekly_report_timezone' => 'Asia/Jakarta',
        ]);

    $response->assertRedirect('/settings/notifications');
    $response->assertSessionHas('success', 'Weekly report preferences updated successfully.');

    $user->refresh();
    expect($user->weekly_report_enabled)->toBeFalse();
    expect($user->weekly_report_timezone)->toBe('Asia/Jakarta');
});

test('updating weekly report preferences validates input', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/settings/notifications')
        ->patch('/settings/weekly-report', [
            'weekly_report_enabled' => 'not-a-bool',
            'weekly_report_timezone' => 'Invalid/Timezone',
        ]);

    $response->assertSessionHasErrors(['weekly_report_enabled', 'weekly_report_timezone']);
});
