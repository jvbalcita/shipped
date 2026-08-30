<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Release;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;

test('following a creator surfaces their actions in the feed', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    $member = verifiedUser();
    $member->followings()->create([
        'followable_type' => 'user',
        'followable_id' => $creator->id,
    ]);

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use ($project, $creator) {
            $page->component('Feed/Index')
                ->where('empty', false)
                ->where('followedCreators', 1)
                ->where('followedProjects', 0)
                ->has('activities.items', 1)
                ->where('activities.items.0.verb', 'released')
                ->where('activities.items.0.project.slug', (string) $project->slug)
                ->where('activities.items.0.actor.username', $creator->username);
        });
});

test('following a project surfaces events on that project', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    Review::factory()->for($project)->for(User::factory(), 'user')->create(['rating' => 5]);
    $member = verifiedUser();
    $member->followings()->create([
        'followable_type' => 'project',
        'followable_id' => $project->id,
    ]);

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('followedProjects', 1)
            ->has('activities.items', 1)
            ->where('activities.items.0.verb', 'reviewed'));
});

test('an event matching a followed creator and a followed project appears once', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    $member = verifiedUser();
    $member->followings()->createMany([
        ['followable_type' => 'user', 'followable_id' => $creator->id],
        ['followable_type' => 'project', 'followable_id' => $project->id],
    ]);

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('followedCreators', 1)
            ->where('followedProjects', 1)
            ->has('activities.items', 1));
});

test('activities outside the follow graph never appear', function () {
    $stranger = User::factory()->create();
    $project = Project::factory()->for($stranger, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->actingAs(verifiedUser())
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('empty', true)
            ->has('activities.items', 0)
            ->where('activities.next_cursor', null));
});

test('scheduled releases stay hidden until they are due', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->addDay()]);
    $member = verifiedUser();
    $member->followings()->create([
        'followable_type' => 'user',
        'followable_id' => $creator->id,
    ]);

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('empty', true));
});

test('deleted subjects render without their project', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    $project->delete();

    $member = verifiedUser();
    $member->followings()->create([
        'followable_type' => 'user',
        'followable_id' => $creator->id,
    ]);

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('activities.items', 1)
            ->where('activities.items.0.project', null));
});

test('the feed paginates by cursor twenty rows at a time', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    Activity::factory()
        ->count(25)
        ->sequence(fn ($sequence) => [
            'actor_id' => $creator->id,
            'subject_id' => $project->id,
            'occurred_at' => now()->subMinutes($sequence->index + 1),
        ])
        ->create();
    $member = verifiedUser();
    $member->followings()->create([
        'followable_type' => 'user',
        'followable_id' => $creator->id,
    ]);

    $version = (new HandleInertiaRequests)->version(new Request);

    $pageOne = $this->actingAs($member)
        ->get(route('feed'), ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
        ->json('props.activities');

    expect($pageOne['items'])->toHaveCount(20)
        ->and($pageOne['next_cursor'])->toBeString();

    $pageTwo = $this->actingAs($member)
        ->get(route('feed', ['cursor' => $pageOne['next_cursor']]), ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])
        ->json('props.activities');

    expect($pageTwo['items'])->toHaveCount(5);
});

test('a guest visiting the feed is redirected to login', function () {
    $this->get(route('feed'))->assertRedirect(route('login'));
});

test('an empty feed suggests creators with public work to follow', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->filed()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $member = verifiedUser();

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('empty', true)
            ->has('suggestedCreators', 1)
            ->where('suggestedCreators.0.username', $creator->username)
            ->where('suggestedCreators.0.followers_count', 0));
});

test('an empty feed suggests nobody when there is no one left to suggest', function () {
    $privateCreator = User::factory()->create();
    Project::factory()->for($privateCreator, 'creator')->create();

    $member = verifiedUser();

    // The viewer's own public work must not suggest themselves.
    $ownProject = Project::factory()->filed()->public()->for($member, 'creator')->create();
    Release::factory()->for($ownProject)->create(['published_at' => now()]);

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('empty', true)
            ->has('suggestedCreators', 0));
});

test('an active feed does not include creator suggestions', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->filed()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    $member = verifiedUser();
    $member->followings()->create([
        'followable_type' => 'user',
        'followable_id' => $creator->id,
    ]);

    $this->actingAs($member)
        ->get(route('feed'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('empty', false)
            ->has('suggestedCreators', 0));
});
