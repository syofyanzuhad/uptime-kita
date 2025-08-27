<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonitorPerformanceHourly>
 */
class MonitorPerformanceHourlyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $successCount = $this->faker->numberBetween(0, 60);
        $failureCount = $this->faker->numberBetween(0, 10);

        return [
            'monitor_id' => Monitor::factory(),
            'hour' => \Carbon\Carbon::instance($this->faker->dateTimeBetween('-1 week', 'now'))->startOfHour(),
            'avg_response_time' => $this->faker->randomFloat(2, 50, 1000),
            'p95_response_time' => $this->faker->randomFloat(2, 200, 2000),
            'p99_response_time' => $this->faker->randomFloat(2, 500, 5000),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ];
    }
}
