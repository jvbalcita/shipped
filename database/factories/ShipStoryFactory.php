<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ShipStory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipStory>
 */
class ShipStoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'problem' => fake()->sentence(12),
            'audience' => fake()->sentence(10),
            'shipped' => fake()->paragraph(),
            'build_decisions' => fake()->sentence(14),
            'hardest_problem' => fake()->sentence(14),
            'lessons_learned' => fake()->sentence(14),
            'next' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['approved_at' => now()]);
    }
}
