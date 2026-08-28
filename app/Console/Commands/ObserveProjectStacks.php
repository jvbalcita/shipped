<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use App\Services\GitHub\StackObservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ObserveProjectStacks extends Command
{
    protected $signature = 'shipped:observe-project-stacks {--project=}';

    protected $description = 'Refresh observed Built With evidence from the public repository of each discoverable project.';

    public function handle(StackObservationService $observation): int
    {
        $startedAt = hrtime(true);
        $counts = ['observed' => 0, 'matched' => 0, 'failed' => 0, 'stopped_early' => 0];

        // Explicit id-cursor loop: a rate-limited GitHub must stop the
        // whole run so the next scheduled pass starts fresh instead of
        // burning the remaining calls on failures.
        $cursor = 0;

        while (true) {
            $projects = Project::query()
                ->where('is_demo', false)
                ->whereNotNull('github_url')
                ->where('id', '>', $cursor)
                ->when(
                    $this->option('project') !== null,
                    fn ($query) => $query->where('slug', (string) $this->option('project')),
                    // Scheduled runs are freshness maintenance for public
                    // records; unlisted projects observe on creator request.
                    fn ($query) => $query->discoverable(),
                )
                ->orderBy('id')
                ->limit(25)
                ->get();

            if ($projects->isEmpty()) {
                break;
            }

            foreach ($projects as $project) {
                try {
                    $result = $observation->observe($project);
                } catch (GitHubApiUnavailable) {
                    $counts['stopped_early'] = 1;
                    $this->warn('GitHub is unavailable; stopping this run.');
                    break 2;
                }

                if ($result->succeeded()) {
                    $counts['observed']++;
                    $counts['matched'] += count($result->observed);

                    continue;
                }

                $counts['failed']++;
            }

            $cursor = (int) $projects->last()->getKey();
        }

        $durationMs = (int) ((hrtime(true) - $startedAt) / 1e6);

        Log::info('Stack observation completed.', [
            ...$counts,
            'duration_ms' => $durationMs,
        ]);

        $this->info(sprintf(
            'Observed %d project(s): %d match(es), %d unreadable%s, in %d ms.',
            $counts['observed'],
            $counts['matched'],
            $counts['failed'],
            $counts['stopped_early'] === 1 ? ' (stopped early)' : '',
            $durationMs,
        ));

        return self::SUCCESS;
    }
}
