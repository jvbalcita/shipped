<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function shippingProfileProject(User $creator, array $overrides = []): Project
{
    $project = Project::factory()
        ->public()
        ->filed()
        ->for($creator, 'creator')
        ->create($overrides);

    Release::factory()->for($project)->create([
        'published_at' => now()->subDay(),
    ]);

    return $project;
}

test('a creator profile remains useful before the first public project', function () {
    $creator = User::factory()->create();

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Creators/Show')
            ->where('creator.stats.public_projects', 0)
            ->where('creator.stats.verified_projects', 0)
            ->where('creator.stats.ship_stories', 0)
            ->where('creator.stats.releases', 0)
            ->where('featured_projects', [])
            ->where('shipping_history', []));
});

test('a creator profile exposes factual counts and derived shipping history', function () {
    $creator = User::factory()->create();
    $older = shippingProfileProject($creator, ['launch_date' => '2026-08-22']);
    $recent = shippingProfileProject($creator, ['launch_date' => '2026-07-01']);
    $recentRelease = Release::factory()->for($recent)->create([
        'title' => 'A meaningful second release',
        'published_at' => now()->subHour(),
    ]);

    $older->forceFill(['profile_featured_order' => 2])->save();
    $recent->forceFill(['profile_featured_order' => 1])->save();

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Creators/Show')
            ->where('creator.stats.public_projects', 2)
            ->where('creator.stats.verified_projects', 2)
            ->where('creator.stats.ship_stories', 2)
            ->where('creator.stats.releases', 3)
            ->where('profile_url', route('creators.show', $creator))
            ->where('ogImage', route('og.creator', $creator))
            ->where('featured_projects.0.id', $recent->id)
            ->where('featured_projects.1.id', $older->id)
            ->where('shipping_history.0.project.id', $recent->id)
            ->where('shipping_history.0.latest_release.id', $recentRelease->id)
            ->where('shipping_history.0.ship_story_excerpt', $recent->shipStory->excerpt())
            ->where('shipping_history.1.project.id', $older->id));
});

test('a creator profile excludes projects that are not currently discoverable', function () {
    $creator = User::factory()->create();
    $public = shippingProfileProject($creator);
    $private = shippingProfileProject($creator, ['is_public' => false]);
    $stale = Project::factory()
        ->stale()
        ->filed()
        ->for($creator, 'creator')
        ->create(['is_public' => true]);
    Release::factory()->for($stale)->create();
    $failed = Project::factory()
        ->failed()
        ->filed()
        ->for($creator, 'creator')
        ->create(['is_public' => true]);
    Release::factory()->for($failed)->create();
    $unapproved = shippingProfileProject($creator);
    $unapproved->shipStory()->update(['approved_at' => null]);

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('creator.stats.public_projects', 1)
            ->where('creator.stats.verified_projects', 1)
            ->where('creator.stats.ship_stories', 1)
            ->where('shipping_history.0.project.id', $public->id)
            ->missing('shipping_history.1'));

    expect($private->isPubliclyDiscoverable())->toBeFalse()
        ->and($stale->isPubliclyDiscoverable())->toBeFalse()
        ->and($failed->isPubliclyDiscoverable())->toBeFalse()
        ->and($unapproved->isPubliclyDiscoverable())->toBeFalse();
});

test('a creator can pin up to three discoverable projects in a chosen order', function () {
    $creator = verifiedUser();
    $projects = collect(range(1, 3))
        ->map(fn (int $number): Project => shippingProfileProject($creator, [
            'name' => "Pinned Project {$number}",
        ]));

    $this->actingAs($creator)
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => [$projects[2]->id, $projects[0]->id],
        ])
        ->assertRedirect(route('profile.edit'));

    expect($projects[2]->refresh()->profile_featured_order)->toBe(1)
        ->and($projects[0]->refresh()->profile_featured_order)->toBe(2)
        ->and($projects[1]->refresh()->profile_featured_order)->toBeNull();
});

test('an authenticated unverified creator can curate their shipping profile', function () {
    $creator = User::factory()->create();
    $project = shippingProfileProject($creator);

    $this->actingAs($creator)
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => [$project->id],
        ])
        ->assertRedirect(route('profile.edit'));

    expect($project->refresh()->profile_featured_order)->toBe(1);
});

test('creator settings expose owned projects and current discoverability for curation', function () {
    $creator = verifiedUser();
    $public = shippingProfileProject($creator, ['name' => 'Public Candidate']);
    $private = shippingProfileProject($creator, [
        'name' => 'Private Candidate',
        'is_public' => false,
    ]);

    $this->actingAs($creator)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Profile')
            ->has('profileProjects', 2)
            ->where('profileProjects', function ($projects) use ($private, $public): bool {
                $projectsById = collect($projects)->keyBy('id');

                return data_get($projectsById->get($private->id), 'is_discoverable') === false
                    && data_get($projectsById->get($public->id), 'is_discoverable') === true;
            }));
});

test('a public project keeps the Creator identity needed for profile navigation', function () {
    $creator = User::factory()->create(['username' => 'profile-maker']);
    $project = shippingProfileProject($creator);

    $this->get(route('projects.show', [$creator, $project]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Show')
            ->where('project.creator.username', $creator->username));
});

test('featured project curation rejects more than three projects', function () {
    $creator = verifiedUser();
    $projects = collect(range(1, 4))
        ->map(fn (int $number): Project => shippingProfileProject($creator, [
            'name' => "Candidate Project {$number}",
        ]));

    $this->actingAs($creator)
        ->from(route('profile.edit'))
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => $projects->pluck('id')->all(),
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('project_ids');

    expect($projects->every(fn (Project $project): bool => $project->refresh()->profile_featured_order === null))
        ->toBeTrue();
});

test('featured project curation rejects duplicate project ids', function () {
    $creator = verifiedUser();
    $project = shippingProfileProject($creator);

    $this->actingAs($creator)
        ->from(route('profile.edit'))
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => [$project->id, $project->id],
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('project_ids.1');

    expect($project->refresh()->profile_featured_order)->toBeNull();
});

test('featured project curation rejects projects belonging to another creator', function () {
    $creator = verifiedUser();
    $otherCreator = User::factory()->create();
    $otherProject = shippingProfileProject($otherCreator);

    $this->actingAs($creator)
        ->from(route('profile.edit'))
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => [$otherProject->id],
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('project_ids');

    expect($otherProject->refresh()->profile_featured_order)->toBeNull();
});

test('a pinned project that becomes private is hidden from the public profile', function () {
    $creator = verifiedUser();
    $project = shippingProfileProject($creator);

    $this->actingAs($creator)
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => [$project->id],
        ])
        ->assertRedirect(route('profile.edit'));

    $project->forceFill(['is_public' => false])->save();

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('featured_projects', [])
            ->where('shipping_history', []));
});

test('a hidden featured project keeps its order while public curation changes', function () {
    $creator = verifiedUser();
    $hiddenProject = shippingProfileProject($creator, ['name' => 'Hidden Project']);
    $newProject = shippingProfileProject($creator, ['name' => 'New Project']);

    $this->actingAs($creator)
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => [$hiddenProject->id],
        ])
        ->assertRedirect(route('profile.edit'));

    $hiddenProject->forceFill(['is_public' => false])->save();

    $this->actingAs($creator)
        ->put(route('profile.featured-projects.update'), [
            'project_ids' => [$newProject->id],
        ])
        ->assertRedirect(route('profile.edit'));

    expect($hiddenProject->refresh()->profile_featured_order)->toBe(1)
        ->and($newProject->refresh()->profile_featured_order)->toBe(2);

    $hiddenProject->forceFill(['is_public' => true])->save();

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('featured_projects.0.id', $hiddenProject->id)
            ->where('featured_projects.1.id', $newProject->id));
});

test('a creator profile serves a self-contained SVG share card', function () {
    $creator = User::factory()->create([
        'name' => 'Ada Lovelace',
        'username' => 'ada',
        'title' => 'Laravel product builder',
    ]);

    $response = $this->get(route('og.creator', $creator));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'image/svg+xml');

    expect($response->getContent())
        ->toContain('ADA LOVELACE')
        ->toContain('@ADA')
        ->toContain('SHIPPING PROFILE')
        ->toContain('SHIPPED');
});
