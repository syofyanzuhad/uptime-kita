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
            'url' => 'https://example'.$this->faker->unique()->numberBetween(1, 100000).'.com',
            'uptime_status' => 'up',
            'uptime_check_enabled' => true,
            'certificate_check_enabled' => false,
            'uptime_check_interval_in_minutes' => 5,
            'is_public' => true,
            'uptime_last_check_date' => now(),
            'uptime_status_last_change_date' => now(),
            'certificate_status' => 'not applicable',
            'certificate_expiration_date' => null,
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
