<?php

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

// No global beforeEach - create users per test to avoid constraint violations

function testSuccessfulUnsubscription()
{
    describe('successful unsubscription', function () {
        it('successfully unsubscribes user from public monitor', function () {
            $user = User::factory()->create();
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://example.com',
            ]);

            // Subscribe user to monitor first
            $monitor->users()->attach($user->id);

            // Set cache to verify it gets cleared
            Cache::put('public_monitors_authenticated_'.$user->id, 'cached_data');

            $response = $this->actingAs($user)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');
            $response->assertSessionHas('flash.message', 'Berhasil berhenti berlangganan monitor: '.$monitor->url);

            // Verify user is unsubscribed
            $this->assertDatabaseMissing('user_monitor', [
                'user_id' => $user->id,
                'monitor_id' => $monitor->id,
            ]);

            // Verify cache is cleared
            $this->assertNull(Cache::get('public_monitors_authenticated_'.$user->id));
        });

        it('successfully unsubscribes user and displays correct URL in message', function () {
            $user = User::factory()->create();
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://example-custom.com',
            ]);

            // Subscribe user to monitor first
            $monitor->users()->attach($user->id);

            $response = $this->actingAs($user)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.message', 'Berhasil berhenti berlangganan monitor: https://example-custom.com');
        });
    });
}

function testValidationAndErrorHandling()
{
    describe('validation and error handling', function () {
        it('prevents unsubscribing from private monitor', function () {
            $user = User::factory()->create();
            $monitor = Monitor::factory()->create([
                'is_public' => false,
                'url' => 'https://private-example.com',
            ]);

            $response = $this->actingAs($user)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Monitor tidak tersedia untuk berlangganan');

            // Verify no subscription exists
            $this->assertDatabaseMissing('user_monitor', [
                'user_id' => $user->id,
                'monitor_id' => $monitor->id,
            ]);
        });

        it('prevents unsubscribing when user is not subscribed', function () {
            $user = User::factory()->create();
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://not-subscribed-example.com',
            ]);

            // User is not subscribed to this monitor
            $response = $this->actingAs($user)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message', 'Anda tidak berlangganan monitor ini');

            // Verify still no subscription
            $this->assertDatabaseMissing('user_monitor', [
                'user_id' => $user->id,
                'monitor_id' => $monitor->id,
            ]);
        });

        it('handles monitor not found gracefully', function () {
            $user = User::factory()->create();
            $nonExistentMonitorId = 99999;

            $response = $this->actingAs($user)->delete("/monitor/{$nonExistentMonitorId}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'error');
            $response->assertSessionHas('flash.message');

            // Verify error message contains helpful information
            $flashMessage = session('flash.message');
            $this->assertStringContainsString('Gagal berhenti berlangganan monitor:', $flashMessage);
        });
    });
}

function testAuthenticationAndAuthorization()
{
    describe('authentication and authorization', function () {
        it('requires authentication', function () {
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://auth-example.com',
            ]);

            $response = $this->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect('/login');
        });

        it('only affects current user subscription', function () {
            $currentUser = User::factory()->create();
            $otherUser = User::factory()->create();

            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://multi-user-example.com',
            ]);

            // Both users subscribe to the monitor
            $monitor->users()->attach([$currentUser->id, $otherUser->id]);

            $response = $this->actingAs($currentUser)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');

            // Verify only current user is unsubscribed
            $this->assertDatabaseMissing('user_monitor', [
                'user_id' => $currentUser->id,
                'monitor_id' => $monitor->id,
            ]);

            // Verify other user still subscribed
            $this->assertDatabaseHas('user_monitor', [
                'user_id' => $otherUser->id,
                'monitor_id' => $monitor->id,
            ]);
        });
    });
}

function testCacheManagement()
{
    describe('cache management', function () {
        it('clears user-specific monitor cache after unsubscription', function () {
            $user = User::factory()->create();
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://cache-example.com',
            ]);

            // Subscribe user to monitor
            $monitor->users()->attach($user->id);

            // Set cache for current user
            $cacheKey = 'public_monitors_authenticated_'.$user->id;
            Cache::put($cacheKey, 'some_cached_data');

            // Verify cache exists before unsubscription
            $this->assertNotNull(Cache::get($cacheKey));

            $response = $this->actingAs($user)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');

            // Verify cache is cleared after unsubscription
            $this->assertNull(Cache::get($cacheKey));
        });

        it('does not affect other users cache when unsubscribing', function () {
            $currentUser = User::factory()->create();
            $otherUser = User::factory()->create();

            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://multi-cache-example.com',
            ]);

            // Subscribe both users
            $monitor->users()->attach([$currentUser->id, $otherUser->id]);

            // Set cache for both users
            $currentUserCacheKey = 'public_monitors_authenticated_'.$currentUser->id;
            $otherUserCacheKey = 'public_monitors_authenticated_'.$otherUser->id;

            Cache::put($currentUserCacheKey, 'current_user_data');
            Cache::put($otherUserCacheKey, 'other_user_data');

            $response = $this->actingAs($currentUser)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');

            // Verify only current user's cache is cleared
            $this->assertNull(Cache::get($currentUserCacheKey));
            $this->assertNotNull(Cache::get($otherUserCacheKey));
            $this->assertEquals('other_user_data', Cache::get($otherUserCacheKey));
        });
    });
}

function testEdgeCasesAndDataIntegrity()
{
    describe('edge cases and data integrity', function () {
        it('handles multiple subscriptions correctly', function () {
            $user = User::factory()->create();
            $monitor1 = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://monitor1-example.com',
            ]);
            $monitor2 = Monitor::factory()->create([
                'is_public' => true,
                'url' => 'https://monitor2-example.com',
            ]);

            // Subscribe to both monitors
            $monitor1->users()->attach($user->id);
            $monitor2->users()->attach($user->id);

            // Unsubscribe from first monitor
            $response = $this->actingAs($user)->delete("/monitor/{$monitor1->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');

            // Verify unsubscribed from monitor1 but not monitor2
            $this->assertDatabaseMissing('user_monitor', [
                'user_id' => $user->id,
                'monitor_id' => $monitor1->id,
            ]);

            $this->assertDatabaseHas('user_monitor', [
                'user_id' => $user->id,
                'monitor_id' => $monitor2->id,
            ]);
        });

        it('works with monitors that have withoutGlobalScopes applied', function () {
            $user = User::factory()->create();
            // Create monitor that might normally be filtered by global scopes
            $monitor = Monitor::withoutGlobalScopes()->create([
                'is_public' => true,
                'url' => 'https://test-scope.com',
                'uptime_status' => 'up',
                'uptime_check_enabled' => true,
                'certificate_check_enabled' => false,
                'uptime_check_interval_in_minutes' => 5,
                'uptime_last_check_date' => now(),
                'uptime_status_last_change_date' => now(),
                'certificate_status' => 'not applicable',
            ]);

            // Subscribe user to monitor
            $monitor->users()->attach($user->id);

            $response = $this->actingAs($user)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');
            $response->assertSessionHas('flash.message', 'Berhasil berhenti berlangganan monitor: https://test-scope.com');

            // Verify unsubscription worked
            $this->assertDatabaseMissing('user_monitor', [
                'user_id' => $user->id,
                'monitor_id' => $monitor->id,
            ]);
        });

        it('handles URL property correctly with empty string URL', function () {
            $user = User::factory()->create();
            $monitor = Monitor::factory()->create([
                'is_public' => true,
                'url' => '', // Test empty URL instead of null
            ]);

            // Subscribe user to monitor
            $monitor->users()->attach($user->id);

            $response = $this->actingAs($user)->delete("/monitor/{$monitor->id}/unsubscribe");

            $response->assertRedirect();
            $response->assertSessionHas('flash.type', 'success');

            // Verify success message handles empty URL gracefully
            $flashMessage = session('flash.message');
            $this->assertStringContainsString('Berhasil berhenti berlangganan monitor:', $flashMessage);
        });
    });
}

describe('UnsubscribeMonitorController', function () {
    testSuccessfulUnsubscription();
    testValidationAndErrorHandling();
    testAuthenticationAndAuthorization();
    testCacheManagement();
    testEdgeCasesAndDataIntegrity();
});
