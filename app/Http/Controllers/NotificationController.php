<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): Response
    {
        $user = $this->currentUser();

        // Cursor pagination needs real columns, so the keyset is plain
        // id-desc recency; unread rows are floated to the top of each
        // page in PHP (recipient sets are small at v1 scale).
        $page = $user->notifications()
            ->with('actor:id,name,username')
            ->orderByDesc('id')
            ->cursorPaginate(self::PER_PAGE);

        $rows = $page->getCollection()
            ->filter(fn (Notification $notification) => $notification->read_at === null)
            ->sortByDesc('id')
            ->concat(
                $page->getCollection()
                    ->filter(fn (Notification $notification) => $notification->read_at !== null)
                    ->sortByDesc('id'),
            )
            ->values();

        $projects = Project::query()
            ->with('creator:id,name,username')
            ->whereIn('id', $rows->where('subject_type', 'project')->pluck('subject_id')->unique())
            ->get()
            ->keyBy('id');

        $items = $rows->map(fn (Notification $notification): array => [
            'id' => $notification->id,
            'type' => $notification->type,
            'actor' => $notification->actor_id !== null && $notification->actor !== null
                ? $notification->actor->only('name', 'username')
                : null,
            'project' => ($project = $projects->get($notification->subject_id)) !== null
                ? [
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'creator_username' => $project->creator?->username,
                ]
                : null,
            'data' => $notification->data,
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ])->values()->all();

        // Viewing the page marks the displayed rows read; the mapping above
        // already captured the unread styling for this render.
        $user->notifications()
            ->whereIn('id', $rows->modelKeys())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Notifications/Index', [
            'notifications' => [
                'items' => $items,
                'next_cursor' => $page->nextCursor()?->encode(),
            ],
        ]);
    }

    public function markAllRead(): RedirectResponse
    {
        $this->currentUser()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
