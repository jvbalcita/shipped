<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshCloudVerifications extends Command
{
    protected $signature = 'shipped:refresh-cloud-verifications';

    protected $description = 'Recheck Laravel Cloud URL verification evidence for URL-backed projects.';

    public function handle(ProjectVerificationService $verificationService): int
    {
        $startedAt = hrtime(true);
        $counts = ['checked' => 0, 'verified' => 0, 'failed' => 0, 'stale' => 0, 'legacy_pending' => 0, 'exceptions' => 0];

        Project::query()
            ->where(function ($query): void {
                $query
                    ->whereNotNull('laravel_cloud_url')
                    ->orWhereNotNull('connected_environment_id');
            })
            ->where('is_demo', false)
            ->chunkById(100, function (Collection $projects) use ($verificationService, &$counts): void {
                foreach ($projects as $project) {
                    if ($project->cloudUrl() === null) {
                        $verificationService->invalidate(
                            $project,
                            ProjectVerificationService::LEGACY_MIGRATION_REASON,
                        );
                        $counts['legacy_pending']++;

                        continue;
                    }

                    try {
                        $status = $verificationService->refresh($project)->verification_status;
                    } catch (Throwable $exception) {
                        $counts['exceptions']++;

                        $verificationService->invalidate(
                            $project,
                            'The Laravel Cloud URL could not be rechecked. Try again shortly.',
                        );

                        // Structured, payload-free context: never the URL,
                        // DNS answers, or anything token-derived.
                        Log::warning('Laravel Cloud URL recheck failed.', [
                            'project_id' => $project->id,
                            'attempt_source' => 'scheduled',
                            'exception' => $exception::class,
                        ]);
                        report($exception);
                        $this->error("Unable to recheck Laravel Cloud URL for project {$project->id}.");

                        continue;
                    }

                    $counts['checked']++;
                    $counts[$status] = ($counts[$status] ?? 0) + 1;
                }
            });

        $durationMs = (int) ((hrtime(true) - $startedAt) / 1e6);

        Log::info('Laravel Cloud URL recheck completed.', [
            ...$counts,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Rechecked %d project(s): %d verified, %d failed, %d stale, %d exception(s), legacy pending %d in %d ms.',
            $counts['checked'],
            $counts['verified'],
            $counts['failed'],
            $counts['stale'],
            $counts['exceptions'],
            $counts['legacy_pending'],
            $durationMs,
        ));

        return self::SUCCESS;
    }
}
