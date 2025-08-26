<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonitorUptimeDaily>
 */
class MonitorUptimeDailyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => \App\Models\Monitor::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'uptime_percentage' => $this->faker->randomFloat(2, 85, 100),
            'avg_response_time' => $this->faker->randomFloat(2, 100, 1000),
            'min_response_time' => $this->faker->randomFloat(2, 50, 500),
            'max_response_time' => $this->faker->randomFloat(2, 500, 3000),
            'total_checks' => $this->faker->numberBetween(100, 1440),
            'failed_checks' => $this->faker->numberBetween(0, 50),
        ];
    }
}
