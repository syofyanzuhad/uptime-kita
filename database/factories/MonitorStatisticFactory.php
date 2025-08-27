<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonitorStatistic>
 */
class MonitorStatisticFactory extends Factory
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
            'uptime_1h' => $this->faker->randomFloat(2, 90, 100),
            'uptime_24h' => $this->faker->randomFloat(2, 85, 100),
            'uptime_7d' => $this->faker->randomFloat(2, 80, 100),
            'uptime_30d' => $this->faker->randomFloat(2, 75, 100),
            'uptime_90d' => $this->faker->randomFloat(2, 70, 100),
            'avg_response_time_24h' => $this->faker->numberBetween(100, 1000),
            'min_response_time_24h' => $this->faker->numberBetween(50, 200),
            'max_response_time_24h' => $this->faker->numberBetween(500, 2000),
            'incidents_24h' => $this->faker->numberBetween(0, 5),
            'incidents_7d' => $this->faker->numberBetween(0, 20),
            'incidents_30d' => $this->faker->numberBetween(0, 50),
            'total_checks_24h' => $this->faker->numberBetween(1000, 1440),
            'total_checks_7d' => $this->faker->numberBetween(5000, 10080),
            'total_checks_30d' => $this->faker->numberBetween(20000, 43200),
            'recent_history_100m' => $this->faker->optional()->randomElements(['up', 'down', 'recovery'], $this->faker->numberBetween(50, 100), true),
            'calculated_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }
}
