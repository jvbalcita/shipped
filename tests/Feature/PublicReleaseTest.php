<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;

test('a published release has a creator-scoped public record', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    $release = Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->get(route('releases.show', compact('creator', 'project', 'release')))
        ->assertSuccessful()
        ->assertSee($release->title);
});

test('a scheduled release and a release from another project return 404', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);
    $scheduledRelease = Release::factory()->for($project)->create(['published_at' => now()->addDay()]);
    $otherRelease = Release::factory()->for(Project::factory()->public()->for($creator, 'creator')->create())->create();

    $this->get(route('releases.show', ['creator' => $creator, 'project' => $project, 'release' => $scheduledRelease]))->assertNotFound();
    $this->get(route('releases.show', ['creator' => $creator, 'project' => $project, 'release' => $otherRelease]))->assertNotFound();
});

test('a release on a private project returns 404', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    $release = Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->get(route('releases.show', compact('creator', 'project', 'release')))->assertNotFound();
});
