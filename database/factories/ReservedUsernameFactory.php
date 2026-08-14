<?php

namespace Database\Factories;

use App\Models\ReservedUsername;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservedUsername>
 */
class ReservedUsernameFactory extends Factory
{
    protected $model = ReservedUsername::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->regexify('[a-z][a-z0-9_]{2,19}'),
            'user_id' => User::factory(),
            'expires_at' => now()->addDays(30),
        ];
    }
}
