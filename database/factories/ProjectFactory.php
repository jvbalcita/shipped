<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
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
            'category_id' => Category::factory(),
            'name' => $name = fake()->unique()->company(),
            'slug' => str($name)->slug(),
            'tagline' => fake()->sentence(8),
            'description' => fake()->paragraph(),
            'live_url' => fake()->url(),
            'github_url' => fake()->url(),
            'pricing' => 'free',
            'launch_date' => null,
            'is_public' => false,
            'verification_status' => 'unverified',
        ];
    }

    public function public(): static
    {
        return $this->verified()->state(fn () => ['is_public' => true]);
    }

    public function filed(): static
    {
        return $this->state(fn () => [
            'filed_number' => (int) (Project::query()->max('filed_number') ?? 0) + 1,
            'filed_at' => now(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'unverified',
            'verified_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'failed',
            'verified_at' => null,
        ]);
    }

    public function stale(): static
    {
        return $this->state(fn () => [
            'verification_status' => 'stale',
            'verified_at' => null,
        ]);
    }

    public function demo(): static
    {
        return $this->state(fn () => ['is_demo' => true]);
    }
}
