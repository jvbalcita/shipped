<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

test('a project description can store rich text markup', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $description = '<p>Built with <strong>Laravel</strong> and <em>Vue</em>.</p><ul><li>Fast</li><li>Open</li></ul>';

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'name' => 'Rich Project',
            'description' => $description,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $project = Project::query()->where('name', 'Rich Project')->first();

    expect($project)->not->toBeNull();
    expect($project->description)->toBe($description);
});

test('a project description can be updated with rich text markup', function () {
    $creator = User::factory()->create();
    $category = Category::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'category_id' => $category->id,
        'description' => 'Old plain text description.',
    ]);

    $description = '<p>Now with a <a href="https://example.com">link</a>.</p>';

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), [
            'description' => $description,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($project->refresh()->description)->toBe($description);
});

test('dangerous markup is stripped from the rendered project description', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'description' => '<p>Safe text.</p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">x</a>',
    ]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Projects/Show')
            ->where('project.description', fn (string $description): bool => ! str_contains($description, '<script')
                && ! str_contains($description, 'onerror')
                && ! str_contains($description, 'javascript:')
                && str_contains($description, '<p>Safe text.</p>')));
});
