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
    public function configure(): static
    {
        return $this->afterCreating(function (Project $project): void {
            if ($project->is_public || $project->is_demo || $project->verification_status === 'verified') {
                $this->createApprovedShipStory($project);
            }
        });
    }

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
            'laravel_cloud_url' => null,
            'verification_method' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'laravel_cloud_url' => 'https://'.str($this->faker->unique()->domainWord()).'-main.laravel.cloud',
            'verification_method' => 'cloud_url',
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'laravel_cloud_url' => 'https://'.str($this->faker->unique()->domainWord()).'-main.laravel.cloud',
            'verification_method' => 'cloud_url',
            'verification_status' => 'failed',
            'verified_at' => null,
        ]);
    }

    public function stale(): static
    {
        return $this->state(fn () => [
            'laravel_cloud_url' => 'https://'.str($this->faker->unique()->domainWord()).'-main.laravel.cloud',
            'verification_method' => 'cloud_url',
            'verification_status' => 'stale',
            'verified_at' => null,
        ]);
    }

    public function demo(): static
    {
        return $this->state(fn () => ['is_demo' => true]);
    }

    private function createApprovedShipStory(Project $project): void
    {
        $story = $project->shipStory()->firstOrNew();
        $story->fill([
            'problem' => 'A focused problem deserves a clear explanation.',
            'audience' => 'People who need a practical solution to this problem.',
            'shipped' => 'A working release that people can try today.',
            'build_decisions' => 'A small, deliberate Laravel stack kept the product easy to change.',
            'hardest_problem' => 'The hardest part was turning the rough idea into a useful workflow.',
            'lessons_learned' => 'The smallest useful slice taught us what to build next.',
        ]);
        $story->approved_at = now();
        $story->save();
    }
}
