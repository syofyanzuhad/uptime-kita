<?php

use App\Models\Monitor;
use App\Models\MonitorHistory;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
    $this->monitor->users()->attach($this->user->id);
});

describe('Monitor History API', function () {
    it('returns history for a monitor', function () {
        MonitorHistory::factory()->count(5)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->monitor->id,
                'uptime_status' => 'up',
                'created_at' => now()->subMinutes($sequence->index),
                'checked_at' => now()->subMinutes($sequence->index),
            ]
        )->create();

        $response = actingAs($this->user)->get("/monitor/{$this->monitor->id}/history");

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'histories');
        $response->assertJsonStructure([
            'histories' => [
                '*' => [
                    'id',
                    'uptime_status',
                    'response_time',
                    'status_code',
                    'message',
                    'created_at',
                    'checked_at',
                ]
            ]
        ]);
    });

    it('limits history to 100 records', function () {
        // Create 150 history records
        // We use sequence to ensure different timestamps so unique_minute index doesn't complain
        MonitorHistory::factory()->count(150)->sequence(
            fn ($sequence) => [
                'monitor_id' => $this->monitor->id,
                'created_at' => now()->subMinutes($sequence->index),
                'checked_at' => now()->subMinutes($sequence->index),
            ]
        )->create();

        $response = actingAs($this->user)->get("/monitor/{$this->monitor->id}/history");

        $response->assertStatus(200);
        $response->assertJsonCount(100, 'histories');
    });
});
