<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonitorIncident>
 */
class MonitorIncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = \Carbon\Carbon::instance($this->faker->dateTimeBetween('-1 week', 'now'));
        $endedAt = $this->faker->optional(0.8)->passthrough(\Carbon\Carbon::instance($this->faker->dateTimeBetween($startedAt, 'now')));
        $durationMinutes = $endedAt ? $startedAt->diffInMinutes($endedAt) : null;

        return [
            'monitor_id' => Monitor::factory(),
            'type' => $this->faker->randomElement(['down', 'degraded', 'recovered']),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $durationMinutes,
            'reason' => $this->faker->optional(0.7)->sentence(),
            'response_time' => $this->faker->optional(0.6)->numberBetween(0, 5000),
            'status_code' => $this->faker->optional(0.8)->randomElement([0, 200, 301, 400, 403, 404, 500, 502, 503, 504]),
            'down_alert_sent' => $this->faker->boolean(70),
            'last_alert_at_failure_count' => $this->faker->optional(0.7)->numberBetween(1, 21),
        ];
    }

    /**
     * Indicate that the incident is ongoing.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
            'duration_minutes' => null,
        ]);
    }

    /**
     * Indicate that the incident has ended.
     */
    public function ended(): static
    {
        $startedAt = \Carbon\Carbon::instance($this->faker->dateTimeBetween('-1 week', '-1 hour'));
        $endedAt = \Carbon\Carbon::instance($this->faker->dateTimeBetween($startedAt, 'now'));

        return $this->state(fn (array $attributes) => [
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $startedAt->diffInMinutes($endedAt),
        ]);
    }

    /**
     * Indicate that an alert was sent for this incident.
     */
    public function alertSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'down_alert_sent' => true,
            'last_alert_at_failure_count' => $this->faker->numberBetween(1, 13),
        ]);
    }

    /**
     * Indicate that no alert was sent for this incident.
     */
    public function noAlertSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'down_alert_sent' => false,
            'last_alert_at_failure_count' => null,
        ]);
    }
}
