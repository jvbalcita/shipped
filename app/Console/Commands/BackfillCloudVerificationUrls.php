<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\LaravelCloud\LaravelCloudUrl;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackfillCloudVerificationUrls extends Command
{
    protected $signature = 'shipped:backfill-cloud-verification-urls
        {--dry-run : Report backfill candidates without writing (default behaviour)}
        {--apply : Write the resolved Cloud URL onto each unambiguous project}
        {--verify : After applying, recheck each backfilled project through its URL}
        {--chunk=100 : Number of projects processed per chunk}';

    protected $description = 'Backfill per-project Laravel Cloud URLs from legacy connected-environment domains.';

    public function handle(ProjectVerificationService $verification): int
    {
        if ($this->option('verify') && ! $this->option('apply')) {
            $this->error('--verify requires --apply so probes only run against written evidence.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $verify = (bool) $this->option('verify');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $counts = ['considered' => 0, 'backfilled' => 0, 'manual_required' => 0, 'verified' => 0, 'exceptions' => 0];
        $manualProjectIds = [];
        $exceptionProjectIds = [];

        Project::query()
            ->whereNull('laravel_cloud_url')
            ->whereNotNull('connected_environment_id')
            ->with('connectedEnvironment')
            ->chunkById($chunkSize, function (Collection $projects) use ($verification, $apply, $verify, &$counts, &$manualProjectIds, &$exceptionProjectIds): void {
                foreach ($projects as $project) {
                    $counts['considered']++;

                    $environment = $project->connectedEnvironment;
                    $candidates = $environment === null ? [] : $this->cloudUrlCandidates($environment->domains);

                    if (count($candidates) !== 1) {
                        $counts['manual_required']++;
                        $manualProjectIds[] = $project->id;

                        if ($apply) {
                            $verification->invalidate(
                                $project,
                                ProjectVerificationService::LEGACY_MIGRATION_REASON,
                            );
                        }

                        continue;
                    }

                    $counts['backfilled']++;

                    if (! $apply) {
                        continue;
                    }

                    $project->forceFill([
                        'laravel_cloud_url' => $candidates[0],
                        'verification_method' => 'cloud_url',
                        'is_public' => false,
                        'verification_status' => 'unverified',
                        'verified_at' => null,
                        'verification_checked_at' => now(),
                        'verification_failure_reason' => ProjectVerificationService::LEGACY_MIGRATION_REASON,
                    ])->save();

                    if (! $verify) {
                        continue;
                    }

                    try {
                        if ($verification->refresh($project)->verification_status === 'verified') {
                            $counts['verified']++;
                        }
                    } catch (Throwable $exception) {
                        $counts['exceptions']++;
                        $exceptionProjectIds[] = $project->id;
                        report($exception);
                        $this->error("Unable to recheck Laravel Cloud URL for project {$project->id}.");
                    }
                }
            });

        $this->report($apply, $counts, $manualProjectIds, $exceptionProjectIds);

        return self::SUCCESS;
    }

    /**
     * Unique canonical `*.laravel.cloud` origins among the legacy synced
     * domains. Zero or multiple candidates stay manual: the command never
     * guesses which environment proves a project.
     *
     * @return array<int, string>
     */
    private function cloudUrlCandidates(mixed $domains): array
    {
        if (! is_array($domains)) {
            return [];
        }

        $candidates = [];

        foreach ($domains as $domain) {
            if (! is_string($domain) || $domain === '') {
                continue;
            }

            $candidate = LaravelCloudUrl::tryFrom(
                str_starts_with($domain, 'https://') ? $domain : 'https://'.$domain,
            );

            if ($candidate !== null) {
                $candidates[] = $candidate->url();
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<int, int>  $manualProjectIds
     * @param  array<int, int>  $exceptionProjectIds
     */
    private function report(bool $apply, array $counts, array $manualProjectIds, array $exceptionProjectIds): void
    {
        // Only aggregate counters and project IDs are reported — never
        // hostnames, full URLs, or anything derived from stored tokens.
        Log::info('Laravel Cloud URL backfill completed.', [
            'mode' => $apply ? 'apply' : 'dry-run',
            ...$counts,
            'manual_required_project_ids' => $manualProjectIds,
        ]);

        $this->info(sprintf(
            '%s: considered %d legacy project(s), backfilled %d, manual required %d, verified %d, exceptions %d.',
            $apply ? 'Apply complete' : 'Dry-run complete',
            $counts['considered'],
            $counts['backfilled'],
            $counts['manual_required'],
            $counts['verified'],
            $counts['exceptions'],
        ));

        if ($manualProjectIds !== []) {
            $this->warn(sprintf(
                'Manual required for %d project(s): creator must paste their Cloud URL. IDs: %s',
                count($manualProjectIds),
                implode(', ', $manualProjectIds),
            ));
        }

        if ($exceptionProjectIds !== []) {
            $this->warn('Recheck exceptions for project IDs: '.implode(', ', $exceptionProjectIds));
        }
    }
}
