<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
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
            'type' => 'cheer',
            'actor_type' => 'user',
            'actor_id' => User::factory(),
            'subject_type' => 'project',
            'subject_id' => Project::factory(),
            'data' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
