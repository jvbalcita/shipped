<?php

namespace Database\Factories;

use App\Models\Follow;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follow>
 */
class FollowFactory extends Factory
{
    /**
     * Define the model's default state of the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'followable_type' => 'project',
            'followable_id' => Project::factory(),
        ];
    }
}
