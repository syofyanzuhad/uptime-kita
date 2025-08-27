<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\StatusPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StatusPageMonitor>
 */
class StatusPageMonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status_page_id' => StatusPage::factory(),
            'monitor_id' => Monitor::factory(),
            'order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
