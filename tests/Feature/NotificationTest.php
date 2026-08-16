<?php

use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;

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
