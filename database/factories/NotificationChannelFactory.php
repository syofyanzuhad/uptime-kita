<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['email', 'telegram', 'slack', 'webhook']);

        $destination = match ($type) {
            'email' => $this->faker->email(),
            'telegram' => '@'.$this->faker->userName(),
            'slack' => 'https://hooks.slack.com/services/'.$this->faker->sha256(),
            'webhook' => $this->faker->url(),
        };

        return [
            'user_id' => \App\Models\User::factory(),
            'type' => $type,
            'destination' => $destination,
            'is_enabled' => $this->faker->boolean(80),
            'metadata' => [],
        ];
    }
}
