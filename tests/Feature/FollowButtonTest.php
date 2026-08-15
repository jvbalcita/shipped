<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('creator profiles expose follower counts and the viewer follow state', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    $viewer = User::factory()->create();
    $creator->addFollower($viewer);

    $this->actingAs($viewer)
        ->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Creators/Show')
            ->where('creator.followers_count', 1)
            ->where('creator.followed_by_viewer', true));

    auth()->forgetGuards();

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('creator.followers_count', 1)
            ->where('creator.followed_by_viewer', false));
});

test('creator profiles do not expose a follow state for the creator themselves', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('creator.followers_count', 0)
            ->where('creator.followed_by_viewer', false));
});

test('project pages expose follower counts and the viewer follow state', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    $viewer = User::factory()->create();
    $project->addFollower($viewer);

    $this->actingAs($viewer)
        ->get(route('projects.show', [$creator, $project]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->where('project.followers_count', 1)
            ->where('project.followed_by_viewer', true));
});

test('following a creator from their profile updates the follower count', function () {
    $creator = User::factory()->create();
    $member = verifiedUser();

    $this->actingAs($member)
        ->post(route('users.follow.store', $creator));

    $this->actingAs($member)
        ->get(route('creators.show', $creator))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('creator.followers_count', 1)
            ->where('creator.followed_by_viewer', true));

    $this->actingAs($member)
        ->delete(route('users.follow.destroy', $creator));

    $this->actingAs($member)
        ->get(route('creators.show', $creator))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('creator.followers_count', 0)
            ->where('creator.followed_by_viewer', false));
});
