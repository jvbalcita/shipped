<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;

function projectFor(User $creator): Project
{
    return Project::factory()->for($creator, 'creator')->create();
}

test('a new follower notifies the followed creator', function () {
    $creator = User::factory()->create();
    $follower = User::factory()->create();

    $creator->addFollower($follower);

    $notification = Notification::query()->first();
    expect($notification)
        ->not->toBeNull()
        ->and($notification->user_id)->toBe($creator->id)
        ->and($notification->type)->toBe('follow')
        ->and($notification->actor_id)->toBe($follower->id)
        ->and($notification->read_at)->toBeNull();

    // Following a project notifies nobody.
    $project = projectFor(User::factory()->create());
    $project->addFollower($follower);

    expect(Notification::query()->count())->toBe(1);
});

test('cheering a project notifies the project owner once', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $cheerer = User::factory()->create();

    Cheer::factory()->create([
        'user_id' => $cheerer->id,
        'cheerable_type' => 'project',
        'cheerable_id' => $project->id,
    ]);

    // Toggled off and re-cheered: still a single notification.
    $project->cheers()->where('user_id', $cheerer->id)->delete();
    Cheer::factory()->create([
        'user_id' => $cheerer->id,
        'cheerable_type' => 'project',
        'cheerable_id' => $project->id,
    ]);

    expect(Notification::query()->count())->toBe(1)
        ->and(Notification::query()->first()->type)->toBe('cheer');
});

test('reviewing a project notifies the owner with the rating snapshot', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $reviewer = User::factory()->create();

    Review::factory()->for($project)->for($reviewer, 'user')->create(['rating' => 5]);

    $notification = Notification::query()->first();
    expect($notification)
        ->not->toBeNull()
        ->and($notification->type)->toBe('review')
        ->and($notification->data['rating'])->toBe(5);
});

test('commenting on a project notifies the owner', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $commenter = User::factory()->create();

    Comment::factory()->for($project)->for($commenter, 'user')->create();

    $notification = Notification::query()->first();
    expect($notification)
        ->not->toBeNull()
        ->and($notification->type)->toBe('comment')
        ->and($notification->user_id)->toBe($owner->id);
});

test('replying to a comment notifies the parent author, not the project owner', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $author = User::factory()->create();
    $parent = Comment::factory()->for($project)->for($author, 'user')->create();
    $replier = User::factory()->create();

    Comment::factory()->for($project)->for($replier, 'user')->create(['parent_id' => $parent->id]);

    // The parent comment itself notified the owner; the reply notifies the
    // parent author and only them.
    expect(Notification::query()->where('type', 'reply')->count())->toBe(1)
        ->and(Notification::query()->where('type', 'reply')->first()->user_id)->toBe($author->id)
        ->and(Notification::query()->where('type', 'comment')->get()->every(fn (Notification $n) => $n->user_id === $owner->id))->toBeTrue();
});

test('self-actions never notify', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);

    Cheer::factory()->create([
        'user_id' => $owner->id,
        'cheerable_type' => 'project',
        'cheerable_id' => $project->id,
    ]);
    Review::factory()->for($project)->for($owner, 'user')->create();
    Comment::factory()->for($project)->for($owner, 'user')->create();
    $parent = Comment::factory()->for($project)->for($owner, 'user')->create();
    Comment::factory()->for($project)->for($owner, 'user')->create(['parent_id' => $parent->id]);

    expect(Notification::query()->count())->toBe(0);
});

test('the shared unread count matches unread notifications only', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $member = User::factory()->create();

    Cheer::factory()->create(['user_id' => $member->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id]);
    Review::factory()->for($project)->for($member, 'user')->create();
    Notification::query()->first()->forceFill(['read_at' => now()])->save();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('unreadNotificationsCount', 1));
});

test('viewing the notifications page renders rows and marks them read', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $member = User::factory()->create();
    Cheer::factory()->create(['user_id' => $member->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id]);

    $this->actingAs($owner)
        ->get(route('notifications.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Notifications/Index')
            ->has('notifications.items', 1)
            ->where('notifications.items.0.type', 'cheer')
            ->where('notifications.items.0.read', false)
            ->where('notifications.items.0.actor.username', $member->username)
            ->where('notifications.items.0.project.slug', (string) $project->slug));

    expect($owner->unreadNotifications()->count())->toBe(0);
});

test('mark all read clears every unread notification', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $member = User::factory()->create();
    Cheer::factory()->create(['user_id' => $member->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id]);
    Review::factory()->for($project)->for($member, 'user')->create();

    $this->actingAs($owner)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($owner->unreadNotifications()->count())->toBe(0)
        ->and(Notification::query()->whereNotNull('read_at')->count())->toBe(2);
});

test('an empty inbox renders the empty state', function () {
    $this->actingAs(verifiedUser())
        ->get(route('notifications.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notifications.items', 0)
            ->where('notifications.next_cursor', null));
});

test('a guest visiting notifications is redirected to login', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
    $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
});

test('the notifications page paginates twenty rows at a time', function () {
    $owner = User::factory()->create();
    $project = projectFor($owner);
    $member = User::factory()->create();

    foreach (range(1, 25) as $i) {
        Notification::query()->create([
            'user_id' => $owner->id,
            'type' => 'cheer',
            'actor_type' => 'user',
            'actor_id' => $member->id,
            'subject_type' => 'project',
            'subject_id' => $project->id,
            'data' => null,
            'read_at' => now(),
            'created_at' => now()->subMinutes(50 - $i),
        ]);
    }

    $version = (new HandleInertiaRequests)->version(new Request);

    $pageOne = $this->actingAs($owner)
        ->get(route('notifications.index'), ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
        ->json('props.notifications');

    expect($pageOne['items'])->toHaveCount(20)
        ->and($pageOne['next_cursor'])->toBeString();

    $pageTwo = $this->actingAs($owner)
        ->get(route('notifications.index', ['cursor' => $pageOne['next_cursor']]), ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
        ->json('props.notifications');

    expect($pageTwo['items'])->toHaveCount(5);
});
