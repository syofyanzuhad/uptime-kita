<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitorDomainExpirationReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorDomainExpirationReminder>
 */
class MonitorDomainExpirationReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'reminder_key' => 'threshold_30',
            'sent_at' => now(),
        ];
    }
}
