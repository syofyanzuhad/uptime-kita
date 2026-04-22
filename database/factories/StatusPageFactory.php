<?php

namespace Database\Factories;

use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StatusPage>
 */
class StatusPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->company();

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'description' => $this->faker->sentence(),
            'icon' => 'default-icon.svg',
            'path' => Str::slug($title).'-'.$this->faker->randomNumber(4),
            'custom_domain' => null,
            'custom_domain_verified' => false,
            'custom_domain_verification_token' => null,
            'custom_domain_verified_at' => null,
            'force_https' => true,
        ];
    }
}
