<?php

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoStatusPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create a user for the demo status page
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Please run UserSeeder first.');

            return;
        }

        // Check if demo status page already exists
        $existingDemo = StatusPage::query()->where('path', 'demo')->first();
        if ($existingDemo) {
            $this->command->info('Demo status page already exists. Skipping...');

            return;
        }

        // Create the demo status page
        $statusPage = StatusPage::create([
            'user_id' => $user->id,
            'title' => 'Demo Status Page',
            'description' => 'Example status page showcasing Uptime Kita features. Monitor your services and share their status with your users.',
            'icon' => 'activity',
            'path' => 'demo',
        ]);

        $this->command->info('Demo status page created successfully.');

        // Get some public monitors to attach
        $publicMonitors = Monitor::query()
            ->where('is_public', true)
            ->where('uptime_check_enabled', true)
            ->limit(5)
            ->get();

        if ($publicMonitors->isEmpty()) {
            $this->command->warn('No public monitors found to attach to demo status page.');

            return;
        }

        // Attach monitors with order
        $order = 1;
        foreach ($publicMonitors as $monitor) {
            $statusPage->monitors()->attach($monitor->id, ['order' => $order]);
            $order++;
        }

        $this->command->info("Attached {$publicMonitors->count()} monitors to demo status page.");
    }
}
