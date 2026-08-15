<?php

use App\Models\Activity;
use App\Models\Cheer;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Release;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('publishing a release records a released activity with the publish timestamp', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    $publishedAt = now()->subDay()->startOfSecond();

    $release = Release::factory()->for($project)->create(['published_at' => $publishedAt]);

    $activity = Activity::query()->where('verb', 'released')->first();
    expect($activity)
        ->not->toBeNull()
        ->and($activity->subject_id)->toBe($project->id)
        ->and($activity->actor_id)->toBe($creator->id)
        ->and($activity->occurred_at->equalTo($publishedAt))->toBeTrue()
        ->and($activity->meta['release_id'])->toBe($release->id);

    // Re-saving the release must not duplicate the event.
    $release->touch();
    expect(Activity::query()->where('verb', 'released')->count())->toBe(1);
});

test('a draft release records nothing until it has a publish date', function () {
    $project = Project::factory()->for(User::factory(), 'creator')->create();

    $release = Release::factory()->for($project)->create(['published_at' => null]);

    expect(Activity::query()->count())->toBe(0);

    $release->forceFill(['published_at' => now()])->save();

    expect(Activity::query()->where('verb', 'released')->count())->toBe(1);
});

test('the scheduled publisher records a launched activity when the project goes public', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->verified()->for($creator, 'creator')->create(['is_public' => false]);
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    Artisan::call('shipped:publish-scheduled-releases');

    expect($project->refresh()->is_public)->toBeTrue()
        ->and(Activity::query()->where('verb', 'launched')->count())->toBe(1);

    // A second run must not duplicate the launch.
    Artisan::call('shipped:publish-scheduled-releases');
    expect(Activity::query()->where('verb', 'launched')->count())->toBe(1);
});

test('writing a review records a reviewed activity with the rating', function () {
    $project = Project::factory()->for(User::factory(), 'creator')->create();
    $reviewer = User::factory()->create();

    Review::factory()->for($project)->for($reviewer, 'user')->create(['rating' => 4]);

    $activity = Activity::query()->where('verb', 'reviewed')->first();
    expect($activity)
        ->not->toBeNull()
        ->and($activity->actor_id)->toBe($reviewer->id)
        ->and($activity->subject_id)->toBe($project->id)
        ->and($activity->meta['rating'])->toBe(4);
});

test('cheering a project or comment records a cheered activity for the subject project', function () {
    $project = Project::factory()->for(User::factory(), 'creator')->create();

    Cheer::factory()->create([
        'user_id' => User::factory()->create(),
        'cheerable_type' => 'project',
        'cheerable_id' => $project->id,
    ]);

    $comment = Comment::factory()->for($project)->for(User::factory(), 'user')->create();
    Cheer::factory()->create([
        'user_id' => User::factory()->create(),
        'cheerable_type' => 'comment',
        'cheerable_id' => $comment->id,
    ]);

    $activities = Activity::query()->where('verb', 'cheered')->get();
    expect($activities)->toHaveCount(2)
        ->and($activities->pluck('subject_id')->unique()->all())->toBe([$project->id])
        ->and($activities->pluck('meta.cheerable')->sort()->values()->all())->toBe(['comment', 'project']);
});

test('a project transitioning to verified records verified and launched when discoverable', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->unverified()->for($creator, 'creator')->create(['is_public' => true]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $project->forceFill([
        'verification_status' => 'verified',
        'verified_at' => now(),
    ])->save();

    expect(Activity::query()->where('verb', 'verified')->count())->toBe(1)
        ->and(Activity::query()->where('verb', 'launched')->count())->toBe(1);
});

test('verification without a published release records verified but not launched', function () {
    $project = Project::factory()->unverified()->for(User::factory(), 'creator')->create(['is_public' => true]);

    $project->forceFill([
        'verification_status' => 'verified',
        'verified_at' => now(),
    ])->save();

    expect(Activity::query()->where('verb', 'verified')->count())->toBe(1)
        ->and(Activity::query()->where('verb', 'launched')->count())->toBe(0);
});

test('re-running verification on an already verified project records nothing new', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->unverified()->for($creator, 'creator')->create(['is_public' => true]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $verify = fn () => $project->forceFill([
        'verification_status' => 'verified',
        'verified_at' => now(),
        'verification_checked_at' => now(),
    ])->save();

    $verify();
    $verify();

    expect(Activity::query()->where('verb', 'verified')->count())->toBe(1)
        ->and(Activity::query()->where('verb', 'launched')->count())->toBe(1);
});

test('re-verification after a failure records verified again but launched once', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->unverified()->for($creator, 'creator')->create(['is_public' => true]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $project->forceFill(['verification_status' => 'verified', 'verified_at' => now()->subHour()])->save();
    $project->forceFill(['verification_status' => 'failed', 'verified_at' => null])->save();
    $project->forceFill(['verification_status' => 'verified', 'verified_at' => now()])->save();

    expect(Activity::query()->where('verb', 'verified')->count())->toBe(2)
        ->and(Activity::query()->where('verb', 'launched')->count())->toBe(1);
});
