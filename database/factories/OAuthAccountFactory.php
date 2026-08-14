<?php

namespace Database\Factories;

use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OAuthAccount>
 */
class OAuthAccountFactory extends Factory
{
    protected $model = OAuthAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['google', 'github']),
            'provider_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'provider_token' => fake()->sha256(),
            'provider_refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
            'linked_at' => now(),
        ];
    }

    public function google(): static
    {
        return $this->state(fn () => ['provider' => 'google']);
    }

    public function github(): static
    {
        return $this->state(fn () => ['provider' => 'github']);
    }
}
