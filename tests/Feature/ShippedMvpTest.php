<?php

use App\Models\Category;
use App\Models\Cheer;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('a creator can publish a project after creating its first release', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'name' => 'Queue Pilot',
            'tagline' => 'A calm command centre for Laravel queues.',
            'description' => 'A polished queue visibility tool for busy Laravel teams.',
            'live_url' => 'https://queue-pilot.test',
            'github_url' => 'https://github.com/example/queue-pilot',
        ]))
        ->assertRedirect();

    $project = Project::firstOrFail();

    expect($project->is_public)->toBeFalse();

    $shipStory = $project->shipStory()->firstOrFail();
    $shipStory->fill([
        'problem' => 'Teams need a calmer way to understand queue work.',
        'audience' => 'Laravel teams running production queues.',
        'shipped' => 'A focused queue visibility tool.',
        'build_decisions' => 'We kept the workflow inside the existing Laravel project model.',
        'hardest_problem' => 'Making operational state readable without adding noise.',
        'lessons_learned' => 'A small, focused surface is easier to trust.',
    ]);
    $shipStory->approved_at = now();
    $shipStory->save();

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
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($creator)->post(route('projects.store'), validProjectPayload($category, [
        'name' => 'Studio-ready draft',
        'tagline' => 'A draft that is ready for its release story.',
        'description' => 'This draft verifies the redirect into the owner-only project studio.',
    ]));

    $project = Project::query()->firstOrFail();

    $response->assertRedirect(route('projects.edit', $project));
    expect($project->shipStory()->exists())->toBeTrue();

    $this->actingAs($creator)
        ->get(route('projects.edit', $project))
        ->assertSuccessful();
});

test('a public project appears in discovery and its creator profile', function () {
    $creator = User::factory()->create(['username' => 'taylor']);
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    expect(Project::query()->public()->whereKey($project)->exists())->toBeTrue();
    expect($creator->projects()->public()->count())->toBe(1);
});

test('a public project resolves only under its actual creator username', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $otherCreator = User::factory()->create(['username' => 'other_maker']);
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

    expect($project->cheers()->count())->toBe(1);
});

test('project cover uploads use the configured default disk', function () {
    config()->set('filesystems.default', 's3');
    Storage::fake('s3');
    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'name' => 'Shipped',
            'tagline' => 'A community showcase for Laravel Cloud.',
            'description' => 'A community-powered launch feed for Laravel Cloud creators.',
            'cover_image' => UploadedFile::fake()->image('cover.png'),
        ]))
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

test('a creator can remove an existing project cover', function () {
    config()->set('filesystems.default', 's3');
    Storage::fake('s3');

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'cover_image_path' => 'project-covers/original.png',
    ]);

    Storage::disk('s3')->put($project->cover_image_path, 'old cover');

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), [
            'cover_removal' => true,
        ])
        ->assertRedirect(route('projects.edit', $project));

    Storage::disk('s3')->assertMissing('project-covers/original.png');
    expect($project->fresh()->cover_image_path)->toBeNull();
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

test('discovery sorts by most cheered when requested', function () {
    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $cheered = Project::factory()->public()->for($creator, 'creator')->for($category)->create(['name' => 'Beloved Kit']);
    Release::factory()->for($cheered)->create(['published_at' => now()]);
    Cheer::factory()->create(['cheerable_id' => $cheered->id, 'user_id' => User::factory()]);
    Cheer::factory()->create(['cheerable_id' => $cheered->id, 'user_id' => User::factory()]);

    $lonely = Project::factory()->public()->for($creator, 'creator')->for($category)->create(['name' => 'Lonely Kit']);
    Release::factory()->for($lonely)->create(['published_at' => now()]);

    $this->get(route('discover', ['sort' => 'cheered']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.data.0.id', $cheered->id)
            ->where('projects.data.1.id', $lonely->id));
});

test('discovery resolves the active category object for filter chips', function () {
    $creator = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Studio', 'slug' => 'studio']);
    $project = Project::factory()->public()->for($creator, 'creator')->for($category)->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('discover', ['category' => 'studio']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeCategory.slug', 'studio')
            ->where('activeCategory.name', 'Studio'));
});

test('missing records render the branded error page', function () {
    $this->get('/this-record-does-not-exist')
        ->assertNotFound()
        ->assertSee('Not on file.');
});

test('filing a project for the first time assigns a permanent dispatch number', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'is_public' => false,
        'verification_status' => 'verified',
        'verified_at' => now(),
    ]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    expect($project->fresh()->filed_number)->toBeNull();

    $this->actingAs($creator)
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertRedirect();

    $filed = $project->fresh();

    expect($filed->filed_number)->toBe(1)
        ->and($filed->filed_at)->not()->toBeNull()
        ->and($filed->filed_serial)->toBe('DISPATCH 0001');
});

test('each filed project receives the next sequential dispatch number', function () {
    $creator = User::factory()->create();
    $first = Project::factory()->for($creator, 'creator')->create([
        'is_public' => false, 'verification_status' => 'verified', 'verified_at' => now(),
    ]);
    Release::factory()->for($first)->create(['published_at' => now()]);
    $second = Project::factory()->for($creator, 'creator')->create([
        'is_public' => false, 'verification_status' => 'verified', 'verified_at' => now(),
    ]);
    Release::factory()->for($second)->create(['published_at' => now()]);

    $this->actingAs($creator)->patch(route('projects.visibility.update', $first), ['is_public' => true])->assertRedirect();
    $this->actingAs($creator)->patch(route('projects.visibility.update', $second), ['is_public' => true])->assertRedirect();

    expect($first->fresh()->filed_number)->toBe(1)
        ->and($second->fresh()->filed_number)->toBe(2);
});

test('a project keeps its dispatch number if toggled private and public again', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'is_public' => false, 'verification_status' => 'verified', 'verified_at' => now(),
    ]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->actingAs($creator)->patch(route('projects.visibility.update', $project), ['is_public' => true])->assertRedirect();
    expect($project->fresh()->filed_number)->toBe(1);

    $this->actingAs($creator)->patch(route('projects.visibility.update', $project), ['is_public' => false])->assertRedirect();
    $this->actingAs($creator)->patch(route('projects.visibility.update', $project), ['is_public' => true])->assertRedirect();

    expect($project->fresh()->filed_number)->toBe(1)
        ->and($project->fresh()->filed_at)->not()->toBeNull();
});

test('the homepage surfaces a live snapshot of the registry', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->filed()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);
    Cache::forget('shipped:registry:stats');

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('launchCount', 1)
            ->where('creatorCount', 1)
            ->has('latestDispatchAt'));
});

test('a filed launch renders a branded social preview image', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->filed()->public()->for($creator, 'creator')->create(['name' => 'Registry Kit']);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('og.project', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('REGISTRY KIT')
        ->assertSee('DISPATCH')
        ->assertSee('@MAKER');
});

test('a coverless project renders a typographic default cover bearing its name', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->filed()->public()->for($creator, 'creator')->create(['name' => 'Registry Kit', 'cover_image_path' => null]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('cover.project', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('REGISTRY KIT')
        ->assertSee('@MAKER');
});

test('a private draft has no default cover for the public', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->for($creator, 'creator')->create(['name' => 'Stealth Kit']);

    $this->get(route('cover.project', ['creator' => $creator, 'project' => $project]))
        ->assertNotFound();
});

test('the launch page publishes open-graph metadata for crawlers', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->filed()->public()->for($creator, 'creator')->create(['name' => 'Registry Kit', 'tagline' => 'A durable record.']);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertSee('property="og:image"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee(route('og.project', ['creator' => $creator, 'project' => $project], false));
});

test('a private draft has no social preview image', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->for($creator, 'creator')->create(['name' => 'Stealth Kit']);

    $this->get(route('og.project', ['creator' => $creator, 'project' => $project]))
        ->assertNotFound();
});
