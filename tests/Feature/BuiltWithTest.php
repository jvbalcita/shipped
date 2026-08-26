<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\Release;
use App\Models\Technology;
use App\Models\User;
use Database\Seeders\TechnologySeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(TechnologySeeder::class);
});

function seedStack(array $names): array
{
    return Technology::query()->whereIn('name', $names)->pluck('id')->all();
}

test('a creator can declare a built with stack when creating a project', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'name' => 'Stacked Launch',
            'technologies' => ['laravel-13', 'php-85', 'livewire', 'postgresql'],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $project = Project::query()->where('name', 'Stacked Launch')->firstOrFail();

    expect($project->technologies()->orderBy('name')->pluck('name')->all())
        ->toEqualCanonicalizing(['Laravel 13', 'Livewire', 'PHP 8.5', 'PostgreSQL']);
    expect($project->technologies()->pluck('provenance')->unique()->values()->all())
        ->toEqual(['declared']);
});

test('creating a project rejects technologies outside the curated vocabulary', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'technologies' => ['laravel-13', 'not-a-real-stack-item'],
        ]))
        ->assertSessionHasErrors('technologies.1');

    expect(Project::query()->count())->toBe(0);
});

test('a project may declare only one laravel version and one php version', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'technologies' => ['laravel-12', 'laravel-13'],
        ]))
        ->assertSessionHasErrors('technologies');

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'technologies' => ['php-84', 'php-85'],
        ]))
        ->assertSessionHasErrors('technologies');
});

test('updating a project syncs its stack and can clear it', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    $project->technologies()->sync(seedStack(['Laravel 12', 'MySQL']));

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), [
            'name' => $project->name,
            'tagline' => $project->tagline,
            'description' => $project->description,
            'category_id' => $project->category_id,
            'technologies' => ['laravel-13', 'postgresql'],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh()->technologies()->orderBy('name')->pluck('name')->all())
        ->toEqualCanonicalizing(['Laravel 13', 'PostgreSQL']);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), [
            'name' => $project->name,
            'tagline' => $project->tagline,
            'description' => $project->description,
            'category_id' => $project->category_id,
            'technologies' => [],
        ])
        ->assertSessionHasNoErrors();

    expect($project->fresh()->technologies()->count())->toBe(0);
});

test('the public project page exposes the built with stack with provenance', function () {
    $creator = User::factory()->create(['username' => 'stackmaker']);
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    $project->technologies()->sync(seedStack(['Laravel 13', 'Livewire']));
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.built_with', 2)
            ->where('project.built_with.0.provenance', 'declared')
            ->where('project.built_with.0.provenance_label', 'Declared by the creator'));
});

test('discover filters by technology with and semantics across selections', function () {
    $creator = User::factory()->create();

    $both = Project::factory()->public()->for($creator, 'creator')->create(['name' => 'Both Stacks']);
    $both->technologies()->sync(seedStack(['Livewire', 'PostgreSQL']));
    Release::factory()->for($both)->create(['published_at' => now()]);

    $one = Project::factory()->public()->for($creator, 'creator')->create(['name' => 'Livewire Only']);
    $one->technologies()->sync(seedStack(['Livewire']));
    Release::factory()->for($one)->create(['published_at' => now()]);

    $this->get(route('discover', ['technologies' => ['livewire']]))
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 2));

    $this->get(route('discover', ['technologies' => ['livewire', 'postgresql']]))
        ->assertInertia(fn ($page) => $page
            ->has('projects.data', 1)
            ->where('projects.data.0.name', 'Both Stacks'));
});

test('a technology page lists only discoverable projects that declare it', function () {
    $creator = User::factory()->create();
    $technology = Technology::query()->where('slug', 'livewire')->firstOrFail();

    $public = Project::factory()->public()->for($creator, 'creator')->create();
    $public->technologies()->sync(seedStack(['Livewire']));
    Release::factory()->for($public)->create(['published_at' => now()]);

    $private = Project::factory()->for($creator, 'creator')->create(['name' => 'Private Stack']);
    $private->technologies()->sync(seedStack(['Livewire']));
    Release::factory()->for($private)->create(['published_at' => now()]);

    $this->get(route('technologies.show', $technology))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Technologies/Show')
            ->where('technology.name', 'Livewire')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $public->id));
});

test('a technology page stays out of the index until projects back it', function () {
    $empty = Technology::query()->where('slug', 'sql-server')->firstOrFail();

    $this->get(route('technologies.show', $empty))
        ->assertInertia(fn ($page) => $page
            ->where('seo.robots', 'noindex,follow'));

    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    $project->technologies()->sync(seedStack(['SQL Server']));
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('technologies.show', $empty))
        ->assertInertia(fn ($page) => $page
            ->where('seo.robots', 'index,follow'));
});

test('the built with index lists the vocabulary with discoverable project counts', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    $project->technologies()->sync(seedStack(['Livewire']));
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('technologies.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Technologies/Index')
            ->has('groups', 6));
});

test('the sitemap advertises built with urls for technologies with public projects', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    $project->technologies()->sync(seedStack(['Livewire']));
    Release::factory()->for($project)->create(['published_at' => now()]);

    $unused = Technology::query()->where('slug', 'algolia')->firstOrFail();

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee(route('technologies.index'), false)
        ->assertSee(route('technologies.show', 'livewire'), false)
        ->assertDontSee(route('technologies.show', $unused), false);
});
