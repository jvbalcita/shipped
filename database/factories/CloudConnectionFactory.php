<?php

namespace Database\Factories;

use App\Models\CloudConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloudConnection>
 */
class CloudConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'api_token' => fake()->sha256(),
            'status' => 'connected',
            'last_validated_at' => now(),
            'last_error' => null,
        ];
    }
}
