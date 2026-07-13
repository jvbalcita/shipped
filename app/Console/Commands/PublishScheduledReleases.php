<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Release;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class PublishScheduledReleases extends Command
{
    protected $signature = 'shipped:publish-scheduled-releases';

    protected $description = 'Publish verified projects with a due release.';

    public function handle(): int
    {
        $project = new Project;
        $release = new Release;
        $latestRelease = Release::query()
            ->from($release->getTable().' as latest_release')
            ->select('latest_release.id')
            ->whereColumn('latest_release.project_id', $project->qualifyColumn('id'))
            ->orderByDesc('latest_release.published_at')
            ->orderByDesc('latest_release.id')
            ->limit(1);

        Project::query()
            ->where('is_public', false)
            ->where('verification_status', 'verified')
            ->whereHas('releases', fn (Builder $query) => $query
                ->whereIn($release->qualifyColumn('id'), $latestRelease)
                ->where($release->qualifyColumn('published_at'), '<=', now()))
            ->update(['is_public' => true]);

        return self::SUCCESS;
    }
}
