<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFeaturedProjectsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProfileFeaturedProjectsController extends Controller
{
    public function update(UpdateFeaturedProjectsRequest $request): RedirectResponse
    {
        $creator = $request->user();
        $projectIds = collect((array) $request->validated('project_ids'))
            ->map(static fn (mixed $id): int => (int) $id)
            ->values();

        DB::transaction(function () use ($creator, $projectIds): void {
            $discoverableProjectIds = $creator->projects()
                ->discoverable()
                ->pluck('projects.id');

            $creator->projects()
                ->whereKey($discoverableProjectIds)
                ->whereNotNull('profile_featured_order')
                ->update(['profile_featured_order' => null]);

            $preservedOrders = $creator->projects()
                ->whereNotNull('profile_featured_order')
                ->pluck('profile_featured_order')
                ->map(static fn (mixed $order): int => (int) $order)
                ->flip();
            $projects = $creator->projects()
                ->whereKey($projectIds->all())
                ->get()
                ->keyBy('id');

            $nextOrder = 1;
            $projectIds->each(function (int $projectId) use ($projects, $preservedOrders, &$nextOrder): void {
                while ($preservedOrders->has($nextOrder)) {
                    $nextOrder++;
                }

                $project = $projects->get($projectId);

                if ($project === null) {
                    return;
                }

                $project->forceFill(['profile_featured_order' => $nextOrder])->save();
                $nextOrder++;
            });
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Featured projects updated.',
        ]);

        return to_route('profile.edit');
    }
}
