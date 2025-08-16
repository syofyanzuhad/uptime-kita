<?php

namespace Tests\Feature;

use App\Http\Controllers\UnsubscribeMonitorController;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnsubscribeMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_unsubscribe_from_private_monitor()
    {
        $user = User::factory()->create();
        $monitor = Monitor::withoutGlobalScopes()->create([
            'url' => 'https://example.com',
            'is_public' => false,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
            'certificate_check_enabled' => false,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        $this->actingAs($user);

        $controller = new UnsubscribeMonitorController;
        $response = $controller->__invoke($monitor->id);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString(
            'Monitor tidak tersedia untuk berlangganan',
            session('flash.message')
        );
        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'url' => 'https://example.com',
            'is_public' => false,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
            'certificate_check_enabled' => false,
            'uptime_check_interval_in_minutes' => 5,
        ]);
        $this->assertDatabaseCount('monitors', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_user_cannot_unsubscribe_from_monitor_they_are_not_subscribed_to()
    {
        $user = User::factory()->create();
        $monitor = Monitor::withoutGlobalScopes()->create([
            'url' => 'https://example.com',
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
            'certificate_check_enabled' => false,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        $this->actingAs($user);

        $controller = new UnsubscribeMonitorController;
        $response = $controller->__invoke($monitor->id);

        // assert redirect (not JSON response)
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Anda tidak berlangganan monitor ini',
            session('flash.message')
        );
        $this->assertDatabaseMissing('user_monitor', [
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
        ]);
        $this->assertDatabaseCount('user_monitor', 0);
        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'url' => 'https://example.com',
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
            'certificate_check_enabled' => false,
            'uptime_check_interval_in_minutes' => 5,
        ]);
        $this->assertDatabaseCount('monitors', 1);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_successful_unsubscribe_removes_user_monitor_relationship()
    {
        $user = User::factory()->create();
        $monitor = Monitor::withoutGlobalScopes()->create([
            'url' => 'https://example.com',
            'is_public' => true,
            'uptime_check_enabled' => true,
            'uptime_status' => 'up',
            'certificate_check_enabled' => false,
            'uptime_check_interval_in_minutes' => 5,
        ]);

        $this->actingAs($user);

        $controller = new UnsubscribeMonitorController;
        $response = $controller->__invoke($monitor->id);

        $this->assertDatabaseMissing('user_monitor', [
            'user_id' => $user->id,
            'monitor_id' => $monitor->id,
        ]);
    }
}
