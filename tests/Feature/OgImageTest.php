<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;

test('the site share card is a cacheable self-contained SVG', function () {
    $this->get('/og/site.svg')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('SHIPPED', false)
        ->assertSee('1200', false);

    expect($this->get('/og/site.svg')->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=300');
});

test('a release share card is scoped to a published discoverable release', function () {
    $creator = User::factory()->create(['username' => 'card_builder']);
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => 'Card Atlas',
    ]);
    $release = Release::factory()->for($project)->create([
        'title' => 'A bounded release card',
        'published_at' => now()->subMinute(),
    ]);

    $this->get("/og/@card_builder/{$project->slug}/releases/{$release->id}.svg")
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('A BOUNDED RELEASE CARD', false)
        ->assertSee('CARD ATLAS', false)
        ->assertSee('@CARD_BUILDER', false);

    expect($this->get("/og/@card_builder/{$project->slug}/releases/{$release->id}.svg")->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=300');
});

test('private projects and unpublished releases do not receive public share cards', function () {
    $creator = User::factory()->create(['username' => 'private_card_builder']);
    $privateProject = Project::factory()->for($creator, 'creator')->create();
    $privateRelease = Release::factory()->for($privateProject)->create([
        'published_at' => now()->subMinute(),
    ]);

    $this->get(route('og.project', [
        'creator' => $creator,
        'project' => $privateProject,
    ]))->assertNotFound();

    $this->get(route('og.release', [
        'creator' => $creator,
        'project' => $privateProject,
        'release' => $privateRelease,
    ]))->assertNotFound();

    $publicProject = Project::factory()->public()->for($creator, 'creator')->create();
    $draftRelease = Release::factory()->for($publicProject)->create(['published_at' => null]);

    $this->get(route('og.release', [
        'creator' => $creator,
        'project' => $publicProject,
        'release' => $draftRelease,
    ]))->assertNotFound();
});

test('a share card escapes user content instead of emitting markup', function () {
    $creator = User::factory()->create(['username' => 'safe_builder']);
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => '<script>alert(1)</script>',
    ]);
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->get(route('og.project', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertDontSee('<script>', false)
        ->assertSee('&lt;SCRIPT&gt;', false);
});

test('a project share card preserves Unicode when applying editorial casing', function () {
    $creator = User::factory()->create(['username' => 'unicode_builder']);
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => 'Café Ñandú',
    ]);
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->get(route('og.project', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertSee('CAFÉ ÑANDÚ', false);
});
