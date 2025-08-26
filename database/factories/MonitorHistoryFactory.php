<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonitorHistory>
 */
class MonitorHistoryFactory extends Factory
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
            'uptime_status' => $this->faker->randomElement(['up', 'down']),
            'message' => $this->faker->optional(0.7)->sentence(),
            'response_time' => $this->faker->numberBetween(50, 3000),
            'status_code' => $this->faker->randomElement([200, 404, 500, 503]),
            'checked_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }
}
