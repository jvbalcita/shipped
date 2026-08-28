<?php

use App\Models\ProductEvent;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function discoverableProjectFor(User $creator): Project
{
    // A public factory project already carries a complete, approved Ship
    // Story; only the published release is added here.
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    return $project;
}

test('the owner sees the launch kit for a discoverable project', function () {
    $creator = User::factory()->create();
    $project = discoverableProjectFor($creator);

    $this->actingAs($creator)
        ->get(route('projects.launch-kit.show', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/LaunchKit')
            ->where('project.name', $project->name)
            ->where('project.creator.username', $creator->username)
            ->where('kit.is_discoverable', true)
            ->where('kit.canonical_url', rtrim(config('app.url'), '/').'/@'.$creator->username.'/'.$project->slug)
            ->where('kit.badge_markdown', fn (?string $markdown) => str_contains((string) $markdown, "/badges/{$project->slug}.svg"))
            ->where('kit.card_url', fn (?string $url) => str_contains((string) $url, "/og/@{$creator->username}/{$project->slug}"))
            ->where('kit.manifest_url', fn (?string $url) => str_contains((string) $url, "/manifests/{$creator->username}/{$project->slug}.svg"))
            ->where('kit.share_text', fn (string $text) => str_contains($text, $project->name)
                && str_contains($text, $project->tagline)
                && str_contains($text, $project->slug)));
});

test('a private project shows the locked kit without gated assets', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->get(route('projects.launch-kit.show', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/LaunchKit')
            ->where('kit.is_discoverable', false)
            ->where('kit.badge_markdown', null)
            ->where('kit.card_url', null)
            ->where('kit.manifest_url', null));
});

test('a non-owner is forbidden from the launch kit', function () {
    $project = discoverableProjectFor(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->get(route('projects.launch-kit.show', $project))
        ->assertForbidden();
});

test('a guest is redirected to login from the launch kit', function () {
    $project = discoverableProjectFor(User::factory()->create());

    $this->get(route('projects.launch-kit.show', $project))
        ->assertRedirect(route('login'));
});

test('viewing the launch kit records launch_kit_viewed', function () {
    $creator = User::factory()->create();
    $project = discoverableProjectFor($creator);

    $this->actingAs($creator)
        ->get(route('projects.launch-kit.show', $project))
        ->assertOk();

    $event = ProductEvent::query()->where('name', 'launch_kit_viewed')->sole();

    expect($event)
        ->creator_id->toBe($creator->id)
        ->subject_id->toBe($project->id);
});
