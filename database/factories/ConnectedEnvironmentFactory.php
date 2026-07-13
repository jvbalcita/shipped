<?php

namespace Database\Factories;

use App\Models\CloudConnection;
use App\Models\ConnectedEnvironment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConnectedEnvironment>
 */
class ConnectedEnvironmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cloud_connection_id' => CloudConnection::factory(),
            'application_id' => fake()->uuid(),
            'environment_id' => fake()->uuid(),
            'application_name' => fake()->company(),
            'environment_name' => fake()->randomElement(['Production', 'Staging']),
            'domains' => [fake()->domainName()],
            'synced_at' => now(),
        ];
    }
}
