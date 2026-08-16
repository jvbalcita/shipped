<?php

use App\Models\Cheer;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function discoverableProject(): Project
{
    $project = Project::factory()
        ->public()
        ->for(User::factory(), 'creator')
        ->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    return $project;
}

test('the cheer wall renders supporters oldest-first with the first mate on top', function () {
    $project = discoverableProject();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $third = User::factory()->create();

    Cheer::factory()->create(['user_id' => $second->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id, 'created_at' => now()->subMinutes(5)]);
    Cheer::factory()->create(['user_id' => $first->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id, 'created_at' => now()->subMinutes(10)]);
    Cheer::factory()->create(['user_id' => $third->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id, 'created_at' => now()->subMinutes(1)]);

    $this->get(route('projects.show', [$project->creator, $project]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->has('cheers', 3)
            ->where('cheers.0.username', $first->username)
            ->where('cheers.1.username', $second->username)
            ->where('cheers.2.username', $third->username)
            ->where('canCheer', false));
});

test('the launch page exposes the viewer cheer state', function () {
    $project = discoverableProject();
    $viewer = User::factory()->create();
    Cheer::factory()->create(['user_id' => $viewer->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id]);

    $this->actingAs($viewer)
        ->get(route('projects.show', [$project->creator, $project]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasCheered', true)
            ->where('canCheer', true)
            ->has('cheers', 1));
});

test('an empty wall renders the first mate call to action', function () {
    $project = discoverableProject();

    $this->get(route('projects.show', [$project->creator, $project]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('cheers', 0)
            ->where('hasCheered', false));
});

test('private projects expose zero cheer data', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->verified()->for($owner, 'creator')->create(['is_public' => false]);
    Cheer::factory()->create(['user_id' => User::factory()->create()->id, 'cheerable_type' => 'project', 'cheerable_id' => $project->id]);

    $this->actingAs($owner)
        ->get(route('projects.show', [$owner, $project]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cheers', null)
            ->where('canCheer', false));

    // Logged-out visitors cannot even see the project.
    auth()->forgetGuards();
    $this->get(route('projects.show', [$owner, $project]))->assertNotFound();
});

test('cheering from the wall requires authentication', function () {
    $project = discoverableProject();

    $this->post(route('projects.cheers.store', $project))
        ->assertRedirect(route('login'));

    $member = verifiedUser();
    $this->actingAs($member)
        ->post(route('projects.cheers.store', $project))
        ->assertRedirect();

    expect($project->cheers()->where('user_id', $member->id)->exists())->toBeTrue();
});
