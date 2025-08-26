<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = $this->faker->randomElement(['github', 'google', 'facebook', 'twitter']);
        
        return [
            'user_id' => \App\Models\User::factory(),
            'provider_id' => $this->faker->unique()->numerify('##########'),
            'provider_name' => $provider,
        ];
    }
}
