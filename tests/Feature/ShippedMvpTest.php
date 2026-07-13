<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

test('a creator can publish a project after creating its first release', function () {
    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), [
            'name' => 'Queue Pilot',
            'tagline' => 'A calm command centre for Laravel queues.',
            'description' => 'A polished queue visibility tool for busy Laravel teams.',
            'category_id' => $category->id,
            'live_url' => 'https://queue-pilot.test',
            'github_url' => 'https://github.com/example/queue-pilot',
        ])
        ->assertRedirect();

    $project = Project::firstOrFail();

    expect($project->is_public)->toBeFalse();

    $this->actingAs($creator)
        ->post(route('projects.releases.store', $project), [
            'title' => 'Queue Pilot is live',
            'notes' => 'The first public release makes queue work feel unhurried.',
            'timing' => 'now',
        ])
        ->assertRedirect();

    $project->forceFill([
        'verification_status' => 'verified',
        'verified_at' => now(),
    ])->save();

    $this->actingAs($creator)
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertRedirect();

    expect($project->fresh()->is_public)->toBeTrue();
    expect(Release::query()->count())->toBe(1);
});

test('a creator can open the studio after creating a private draft', function () {
    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($creator)->post(route('projects.store'), [
        'name' => 'Studio-ready draft',
        'tagline' => 'A draft that is ready for its release story.',
        'description' => 'This draft verifies the redirect into the owner-only project studio.',
        'category_id' => $category->id,
    ]);

    $project = Project::query()->firstOrFail();

    $response->assertRedirect(route('projects.edit', $project));

    $this->actingAs($creator)
        ->get(route('projects.edit', $project))
        ->assertSuccessful();
});

test('a public project appears in discovery and its creator profile', function () {
    $creator = User::factory()->create(['handle' => 'taylor']);
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    expect(Project::query()->public()->whereKey($project)->exists())->toBeTrue();
    expect($creator->projects()->public()->count())->toBe(1);
});

test('a public project resolves only under its actual creator handle', function () {
    $creator = User::factory()->create(['handle' => 'maker']);
    $otherCreator = User::factory()->create(['handle' => 'other-maker']);
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))->assertSuccessful();
    $this->get(route('projects.show', ['creator' => $otherCreator, 'project' => $project]))->assertNotFound();
});

test('only the project owner can edit it and a cheer is unique per member', function () {
    $creator = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create();

    $this->actingAs($member)
        ->patch(route('projects.update', $project), ['name' => 'Not mine'])
        ->assertForbidden();

    $this->actingAs($member)->post(route('projects.cheers.store', $project))->assertRedirect();
    $this->actingAs($member)->post(route('projects.cheers.store', $project))->assertRedirect();

    expect($project->cheers()->count())->toBe(1);
});

test('project cover uploads use the configured default disk', function () {
    config()->set('filesystems.default', 's3');
    Storage::fake('s3');
    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), [
            'name' => 'Shipped',
            'tagline' => 'A community showcase for Laravel Cloud.',
            'description' => 'A community-powered launch feed for Laravel Cloud creators.',
            'category_id' => $category->id,
            'cover_image' => UploadedFile::fake()->image('cover.png'),
        ])
        ->assertRedirect();

    $project = Project::firstOrFail();

    Storage::disk('s3')->assertExists($project->cover_image_path);
    expect($project->cover_image_url)->toBe(Storage::disk('s3')->url($project->cover_image_path));
});

test('replacing a project cover removes the old file from the configured default disk', function () {
    config()->set('filesystems.default', 's3');
    Storage::fake('s3');

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'cover_image_path' => 'project-covers/original.png',
    ]);

    Storage::disk('s3')->put($project->cover_image_path, 'old cover');

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), [
            'cover_image' => UploadedFile::fake()->image('replacement.png'),
        ])
        ->assertRedirect(route('projects.edit', $project));

    Storage::disk('s3')->assertMissing('project-covers/original.png');
    Storage::disk('s3')->assertExists($project->fresh()->cover_image_path);
});

test('a creator receives project field validation errors before a draft is stored', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->from(route('projects.create'))
        ->post(route('projects.store'), [
            'name' => '',
            'tagline' => '',
            'description' => '',
            'category_id' => null,
            'live_url' => 'not-a-url',
        ])
        ->assertRedirect(route('projects.create'))
        ->assertSessionHasErrors([
            'name',
            'tagline',
            'description',
            'category_id',
            'live_url',
        ]);
});

test('a creator can publish a release immediately or schedule it for later', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->post(route('projects.releases.store', $project), [
            'title' => 'Available now',
            'notes' => 'The first public release.',
            'timing' => 'now',
        ])
        ->assertRedirect();

    expect($project->releases()->firstOrFail()->published_at)->not->toBeNull();

    $this->actingAs($creator)
        ->post(route('projects.releases.store', $project), [
            'title' => 'Coming next week',
            'notes' => 'A future release.',
            'timing' => 'schedule',
            'published_at' => now()->addWeek()->toDateTimeString(),
        ])
        ->assertRedirect();

    expect($project->releases()->where('title', 'Coming next week')->firstOrFail()->published_at->isFuture())->toBeTrue();
});

test('a creator cannot schedule a release in the past', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->travelTo(Carbon::parse('2026-07-13 10:00:00 UTC'));

    $this->actingAs($creator)
        ->from(route('projects.edit', $project))
        ->post(route('projects.releases.store', $project), [
            'title' => 'Already gone',
            'notes' => 'A release must be announced in the future.',
            'timing' => 'schedule',
            'published_at' => '2026-07-13T17:59:00+08:00',
        ])
        ->assertRedirect(route('projects.edit', $project))
        ->assertSessionHasErrors('published_at');
});

test('a creator can schedule a release with an ISO-8601 instant', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    $publicationTime = '2026-07-13T19:30:00+08:00';

    $this->travelTo(Carbon::parse('2026-07-13 10:00:00 UTC'));

    $this->actingAs($creator)
        ->post(route('projects.releases.store', $project), [
            'title' => 'Offset-aware release',
            'notes' => 'The date picker sends an unambiguous instant.',
            'timing' => 'schedule',
            'published_at' => $publicationTime,
        ])
        ->assertRedirect();

    expect($project->releases()->sole()->published_at->toISOString())
        ->toBe(Carbon::parse($publicationTime)->utc()->toISOString());
});

test('scheduled releases and private projects are excluded from public discovery queries', function () {
    $creator = User::factory()->create();
    $visible = Project::factory()->public()->for($creator, 'creator')->create(['name' => 'Tidal Notes']);
    Release::factory()->for($visible)->create(['published_at' => now()]);
    $scheduled = Project::factory()->public()->for($creator, 'creator')->create(['name' => 'Tidal Future']);
    Release::factory()->for($scheduled)->create(['published_at' => now()->addDay()]);
    Project::factory()->for($creator, 'creator')->create(['name' => 'Tidal Draft']);

    $projects = Project::query()->public()->whereHas('releases', fn ($query) => $query->published())->where('name', 'like', '%Tidal%')->get();

    expect($projects->pluck('name')->all())->toBe(['Tidal Notes']);
});

test('discovery supports search and category filters through the public query string', function () {
    $creator = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Package', 'slug' => 'package']);
    $project = Project::factory()->public()->for($creator, 'creator')->for($category)->create(['name' => 'Registry Kit']);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('discover', ['q' => 'Registry', 'category' => 'package']))
        ->assertSuccessful()
        ->assertSee('Registry Kit');
});
