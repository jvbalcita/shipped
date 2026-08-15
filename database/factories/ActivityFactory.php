<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state of the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_type' => 'user',
            'actor_id' => User::factory(),
            'subject_type' => 'project',
            'subject_id' => Project::factory(),
            'verb' => 'released',
            'occurred_at' => now(),
            'meta' => null,
        ];
    }

    public function occurredAt(Carbon $occurredAt): static
    {
        return $this->state(fn () => ['occurred_at' => $occurredAt]);
    }
}
