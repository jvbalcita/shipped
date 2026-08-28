<?php

namespace Database\Factories;

use App\Enums\ContentReportReason;
use App\Enums\ContentReportResolution;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentReport>
 */
class ContentReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => (new Project)->getMorphClass(),
            'reportable_id' => Project::factory(),
            'reason' => ContentReportReason::BrokenLink,
            'note' => null,
        ];
    }

    public function forComment(): static
    {
        return $this->state(fn (): array => [
            'reportable_type' => (new Comment)->getMorphClass(),
            'reportable_id' => Comment::factory(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'resolution' => ContentReportResolution::NoAction,
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
