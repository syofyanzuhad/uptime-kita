<?php

namespace Database\Factories;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'uptime_status' => $this->faker->randomElement(['up', 'down', 'not yet checked']),
            'uptime_check_enabled' => $this->faker->boolean(80), // 80% chance of being enabled
            'certificate_check_enabled' => $this->faker->boolean(),
            'uptime_check_interval_in_minutes' => $this->faker->randomElement([1, 5, 15, 30, 60]),
            'is_public' => $this->faker->boolean(70), // 70% chance of being public
            'uptime_last_check_date' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'uptime_status_last_change_date' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'certificate_status' => $this->faker->randomElement(['valid', 'invalid', 'not applicable']),
            'certificate_expiration_date' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Indicate that the monitor is enabled.
     */
    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'uptime_check_enabled' => true,
        ]);
    }

    /**
     * Indicate that the monitor is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'uptime_check_enabled' => false,
        ]);
    }

    /**
     * Indicate that the monitor is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the monitor is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
