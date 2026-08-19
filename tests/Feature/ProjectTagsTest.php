<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\Release;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('a creator can attach comma-separated tags when creating a project', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'name' => 'Tagged Launch',
            'tags' => 'Laravel, vue, laravel, open-source',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $project = Project::query()->where('name', 'Tagged Launch')->firstOrFail();
    $names = $project->tags()->orderBy('name')->pluck('name')->all();

    expect($names)->toEqualCanonicalizing(['Laravel', 'vue', 'open-source']);
    expect(Tag::query()->count())->toBe(3);
});

test('updating a project syncs tags and can clear them', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    $existing = Tag::factory()->create(['name' => 'old', 'slug' => 'old']);
    $project->tags()->sync([$existing->id]);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), [
            'name' => $project->name,
            'tagline' => $project->tagline,
            'description' => $project->description,
            'category_id' => $project->category_id,
            'tags' => 'pest, inertia',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh()->tags()->pluck('name')->all())->toEqualCanonicalizing(['pest', 'inertia']);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), [
            'name' => $project->name,
            'tagline' => $project->tagline,
            'description' => $project->description,
            'category_id' => $project->category_id,
            'tags' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($project->fresh()->tags()->count())->toBe(0);
});

test('public project pages render tags', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    $tag = Tag::factory()->create(['name' => 'laravel', 'slug' => 'laravel']);
    $project->tags()->attach($tag);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->has('project.tags', 1)
            ->where('project.tags.0.name', 'laravel'));
});
