<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Follow;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    private const PER_PAGE = 20;

    public function __invoke(): Response
    {
        $user = $this->currentUser();

        $activities = $this->feedQuery($user->id)->cursorPaginate(self::PER_PAGE);

        $projects = Project::query()
            ->with('creator:id,name,username')
            ->whereIn('id', $activities->getCollection()->pluck('subject_id')->unique())
            ->get()
            ->keyBy('id');

        return Inertia::render('Feed/Index', [
            'activities' => [
                // Merged on cursor pagination so "Load more" appends rows.
                'items' => Inertia::merge($activities->getCollection()
                    ->map(fn (Activity $activity): array => [
                        'id' => $activity->id,
                        'verb' => $activity->verb,
                        'occurred_at' => $activity->occurred_at->toIso8601String(),
                        'actor' => $activity->actor_id !== null && $activity->actor !== null
                            ? $activity->actor->only('name', 'username')
                            : null,
                        'project' => ($project = $projects->get($activity->subject_id)) !== null
                            ? [
                                'name' => $project->name,
                                'slug' => $project->slug,
                                'creator_username' => $project->creator?->username,
                            ]
                            : null,
                        'meta' => $activity->meta,
                    ])
                    ->values()
                    ->all()),
                'next_cursor' => $activities->nextCursor()?->encode(),
            ],
            'followedCreators' => $user->followedCreators()->count(),
            'followedProjects' => $user->followedProjects()->count(),
            'empty' => $activities->isEmpty(),
            // An empty feed is the one moment the page can redirect
            // attention; suggest who to follow instead of a dead end.
            'suggestedCreators' => $activities->isEmpty()
                ? $this->suggestedCreators($user)
                : [],
        ]);
    }

    /**
     * Members worth following on an empty feed: creators with public,
     * discoverable work that the viewer does not follow yet.
     *
     * @return array<int, array<string, mixed>>
     */
    private function suggestedCreators(User $viewer): array
    {
        $followedCreatorIds = Follow::query()
            ->where('user_id', $viewer->id)
            ->where('followable_type', 'user')
            ->pluck('followable_id');

        return User::query()
            ->whereKeyNot($viewer->id)
            ->whereNotIn('id', $followedCreatorIds)
            ->whereHas('projects', fn (Builder $query) => $query->discoverable())
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->orderBy('name')
            ->limit(3)
            ->get()
            ->map(fn (User $creator): array => [
                'name' => $creator->name,
                'username' => $creator->username,
                'followers_count' => $creator->followers_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Read-time assembly, no fan-out: actions by followed creators union
     * events on followed projects, deduplicated because an event is one row.
     *
     * @return Builder<Activity>
     */
    private function feedQuery(int $userId): Builder
    {
        $followedCreatorIds = Follow::query()
            ->where('user_id', $userId)
            ->where('followable_type', 'user')
            ->select('followable_id');

        $followedProjectIds = Follow::query()
            ->where('user_id', $userId)
            ->where('followable_type', 'project')
            ->select('followable_id');

        return Activity::query()
            ->where(function ($query) use ($followedCreatorIds, $followedProjectIds): void {
                $query
                    ->where(function ($query) use ($followedCreatorIds): void {
                        $query
                            ->where('actor_type', 'user')
                            ->whereIn('actor_id', $followedCreatorIds);
                    })
                    ->orWhere(function ($query) use ($followedProjectIds): void {
                        $query
                            ->where('subject_type', 'project')
                            ->whereIn('subject_id', $followedProjectIds);
                    });
            })
            ->where('occurred_at', '<=', now())
            ->with('actor:id,name,username')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }
}
