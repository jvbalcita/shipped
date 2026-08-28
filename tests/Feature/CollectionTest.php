<?php

use App\Models\Collection;
use App\Models\ProductEvent;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function discoverableProjectForCollection(string $name): Project
{
    // A public factory project already carries a complete, approved Ship
    // Story; only the published release is added here.
    $project = Project::factory()->public()->for(User::factory()->create(), 'creator')->create([
        'name' => $name,
        'slug' => str($name)->slug(),
    ]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    return $project;
}

function curator(): User
{
    $user = User::factory()->create();

    config()->set('shipped.curators', [$user->id]);

    return $user;
}

function actingAsCurator()
{
    return test()->actingAs(curator());
}

test('the collections index lists only collections with live members', function () {
    $member = discoverableProjectForCollection('Curated Member');

    $live = Collection::factory()->create(['title' => 'Live picks', 'slug' => 'live-picks']);
    $live->projects()->attach($member->id, ['position' => 1]);

    Collection::factory()->create(['title' => 'Empty picks', 'slug' => 'empty-picks']);

    $this->get(route('collections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Collections/Index')
            ->where('collections.0.slug', 'live-picks')
            ->where('collections.0.projects_count', 1)
            ->missing('collections.1'));
});

test('a collection page shows the narrative and members in curated order', function () {
    $first = discoverableProjectForCollection('Alpha Pick');
    $second = discoverableProjectForCollection('Beta Pick');

    $collection = Collection::factory()->create([
        'title' => 'Real tools, real ships',
        'slug' => 'real-tools-real-ships',
        'description' => 'Two launches that prove the workflow.',
    ]);
    $collection->projects()->attach([
        $second->id => ['position' => 1],
        $first->id => ['position' => 2],
    ]);

    $this->get(route('collections.show', $collection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Collections/Show')
            ->where('collection.title', 'Real tools, real ships')
            ->where('collection.description', 'Two launches that prove the workflow.')
            ->where('projects.data.0.id', $second->id)
            ->where('projects.data.1.id', $first->id));
});

test('a member that loses discoverability is suppressed but retains its position', function () {
    $visible = discoverableProjectForCollection('Still Discoverable');
    $hidden = discoverableProjectForCollection('Now Private');

    $collection = Collection::factory()->create(['slug' => 'suppression-check']);
    $collection->projects()->attach([
        $hidden->id => ['position' => 1],
        $visible->id => ['position' => 2],
    ]);

    $hidden->forceFill(['is_public' => false])->save();

    $this->get(route('collections.show', ['collection' => 'suppression-check']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Collections/Show')
            ->where('projects.data.0.id', $visible->id)
            ->missing('projects.data.1'));

    // Suppression is display-only: the membership survives with its order.
    expect($collection->projects()->pluck('projects.id')->all())
        ->toBe([$hidden->id, $visible->id]);

    $visible->forceFill(['is_public' => false])->save();

    $this->get(route('collections.show', ['collection' => 'suppression-check']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Collections/Show')
            ->where('projects.total', 0)
            ->where('seo.robots', 'noindex,follow'));
});

test('viewing a collection records a first-party collection_viewed event', function () {
    $collection = Collection::factory()->create();

    $this->get(route('collections.show', $collection))->assertOk();

    $event = ProductEvent::query()->sole();

    expect($event)
        ->name->toBe('collection_viewed')
        ->creator_id->toBeNull()
        ->subject_type->toBe('collection')
        ->subject_id->toBe($collection->id);
});

test('a non-curator cannot manage collections', function () {
    $collection = Collection::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('collections.create'))->assertForbidden();
    $this->post(route('collections.store'), ['title' => 'X', 'description' => 'Y', 'project_ids' => []])->assertForbidden();
    $this->get(route('collections.edit', $collection))->assertForbidden();
    $this->put(route('collections.update', $collection), ['title' => 'X', 'description' => 'Y', 'project_ids' => []])->assertForbidden();
    $this->delete(route('collections.destroy', $collection))->assertForbidden();

    expect(Collection::query()->count())->toBe(1);
});

test('a guest is redirected to login from curator screens', function () {
    $this->get(route('collections.create'))->assertRedirect(route('login'));
});

test('a curator creates a collection with an ordered membership', function () {
    Storage::fake();

    $alpha = discoverableProjectForCollection('Alpha');
    $beta = discoverableProjectForCollection('Beta');

    actingAsCurator()
        ->post(route('collections.store'), [
            'title' => 'Solo ships worth studying',
            'description' => 'Two launches with the full story behind them.',
            'cover_image' => UploadedFile::fake()->image('cover.png'),
            'project_ids' => [$beta->id, $alpha->id],
        ])
        ->assertRedirect(route('collections.show', ['collection' => 'solo-ships-worth-studying']));

    $collection = Collection::query()->where('slug', 'solo-ships-worth-studying')->sole();

    expect($collection->projects()->pluck('projects.id')->all())->toBe([$beta->id, $alpha->id])
        ->and($collection->cover_image_path)->not->toBeNull()
        ->and(Storage::disk()->exists((string) $collection->cover_image_path))->toBeTrue();
});

test('a colliding title still produces a unique slug', function () {
    Collection::factory()->create(['slug' => 'same-slug']);

    actingAsCurator()
        ->post(route('collections.store'), [
            'title' => 'Same Slug',
            'description' => 'Second of the same name.',
            'project_ids' => [],
        ])
        ->assertRedirect();

    expect(Collection::query()->where('slug', 'same-slug-2')->exists())->toBeTrue();
});

test('store validates the narrative and membership', function () {
    actingAsCurator();

    $this->post(route('collections.store'), [
        'title' => '',
        'description' => '',
        'project_ids' => [999999],
    ])->assertInvalid(['title', 'description', 'project_ids.0']);

    $duplicate = discoverableProjectForCollection('Duplicate Target');

    $this->post(route('collections.store'), [
        'title' => 'Duplicates',
        'description' => 'The same member twice.',
        'project_ids' => [$duplicate->id, $duplicate->id],
    ])->assertInvalid('project_ids.1');
});

test('a curator updates the record and reorders members', function () {
    Storage::fake();

    $alpha = discoverableProjectForCollection('Alpha');
    $beta = discoverableProjectForCollection('Beta');
    $gamma = discoverableProjectForCollection('Gamma');

    $collection = Collection::factory()->create(['slug' => 'reorder-me']);
    $collection->projects()->attach([
        $gamma->id => ['position' => 1],
        $alpha->id => ['position' => 2],
    ]);

    actingAsCurator()
        ->put(route('collections.update', ['collection' => 'reorder-me']), [
            'title' => 'Reordered picks',
            'description' => 'New narrative, new order.',
            'project_ids' => [$beta->id, $alpha->id],
        ])
        ->assertRedirect(route('collections.show', ['collection' => 'reordered-picks']));

    $collection->refresh();

    expect($collection->projects()->pluck('projects.id')->all())->toBe([$beta->id, $alpha->id])
        ->and($collection->title)->toBe('Reordered picks');
});

test('destroying a collection leaves its member projects intact', function () {
    $member = discoverableProjectForCollection('Survivor');

    $collection = Collection::factory()->create();
    $collection->projects()->attach($member->id, ['position' => 1]);

    actingAsCurator()
        ->delete(route('collections.destroy', $collection))
        ->assertRedirect(route('collections.index'));

    expect(Collection::query()->count())->toBe(0)
        ->and(Project::query()->whereKey($member->id)->exists())->toBeTrue();
});

test('a signed-in visitor can record a click on a real collection member', function () {
    $member = discoverableProjectForCollection('Clicked Pick');
    $collection = Collection::factory()->create();
    $collection->projects()->attach($member->id, ['position' => 1]);

    $this->actingAs(User::factory()->create())
        ->post(route('product-events.store'), [
            'name' => 'collection_project_clicked',
            'project_id' => $member->id,
            'collection_id' => $collection->id,
        ])
        ->assertCreated();

    $event = ProductEvent::query()->sole();

    expect($event)
        ->name->toBe('collection_project_clicked')
        ->subject_type->toBe('project')
        ->subject_id->toBe($member->id)
        ->properties->toBe(['collection_id' => $collection->id]);
});

test('a click is rejected when the project is not a collection member', function () {
    $outsider = discoverableProjectForCollection('Outsider');
    $collection = Collection::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('product-events.store'), [
            'name' => 'collection_project_clicked',
            'project_id' => $outsider->id,
            'collection_id' => $collection->id,
        ])
        ->assertInvalid('project_id');

    expect(ProductEvent::query()->count())->toBe(0);
});

test('the sitemap includes live collections but not empty ones', function () {
    $member = discoverableProjectForCollection('Indexed Member');

    $live = Collection::factory()->create(['slug' => 'indexed-collection']);
    $live->projects()->attach($member->id, ['position' => 1]);

    Collection::factory()->create(['slug' => 'unindexed-collection']);

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee(route('collections.index'), false)
        ->assertSee(route('collections.show', ['collection' => 'indexed-collection']), false)
        ->assertDontSee('unindexed-collection', false);
});
