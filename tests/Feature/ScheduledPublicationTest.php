<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;

test('a due scheduled release publishes a verified private project', function () {
    $project = Project::factory()->verified()->for(User::factory()->create(), 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($project->fresh()->is_public)->toBeTrue();
});

test('a due scheduled release leaves an unverified project private', function () {
    $project = Project::factory()->unverified()->for(User::factory()->create(), 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($project->fresh()->is_public)->toBeFalse();
});

test('a newer scheduled release keeps a verified project with an older due release private', function () {
    $project = Project::factory()->verified()->for(User::factory()->create(), 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);
    Release::factory()->for($project)->create(['published_at' => now()->addMinute()]);

    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($project->fresh()->is_public)->toBeFalse();
});

test('a due scheduled release leaves failed and stale projects private', function () {
    $creator = User::factory()->create();
    $failedProject = Project::factory()->failed()->for($creator, 'creator')->create();
    $staleProject = Project::factory()->stale()->for($creator, 'creator')->create();
    Release::factory()->for($failedProject)->create(['published_at' => now()->subMinute()]);
    Release::factory()->for($staleProject)->create(['published_at' => now()->subMinute()]);

    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($failedProject->fresh()->is_public)->toBeFalse()
        ->and($staleProject->fresh()->is_public)->toBeFalse();
});

test('scheduled publication is idempotent for public projects', function () {
    $project = Project::factory()->public()->for(User::factory()->create(), 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);
    $updatedAt = $project->updated_at;

    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($project->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});
