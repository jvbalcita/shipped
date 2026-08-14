<?php

use App\Models\Cheer;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('discover cards expose cheer counts and the viewer cheer state', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    $viewer = User::factory()->create();
    Cheer::factory()->create(['cheerable_id' => $project->id, 'user_id' => $viewer->id]);

    $this->actingAs($viewer)
        ->get(route('discover'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Discover/Index')
            ->where('projects.data.0.cheers_count', 1)
            ->where('projects.data.0.cheered_by_viewer', true));
});

test('a guest cheering a project is redirected to login', function () {
    $project = Project::factory()->public()->for(User::factory(), 'creator')->create();

    $this->post(route('projects.cheers.store', $project))
        ->assertRedirect(route('login'));
});
