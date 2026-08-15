<?php

use App\Enums\ProjectPricing;
use App\Models\Category;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a creator can store pricing logo and launch date on a project', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();
    $logo = UploadedFile::fake()->image('logo.png', 256, 256);

    $this->actingAs($creator)
        ->post(route('projects.store'), [
            'name' => 'Northstar',
            'tagline' => 'A calm maintainer home.',
            'description' => 'Longer story about the launch.',
            'category_id' => $category->id,
            'pricing' => ProjectPricing::Freemium->value,
            'launch_date' => '2026-08-01',
            'logo' => $logo,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $project = Project::query()->where('name', 'Northstar')->first();

    expect($project)->not->toBeNull();
    expect($project->pricing)->toBe(ProjectPricing::Freemium);
    expect($project->launch_date?->toDateString())->toBe('2026-08-01');
    expect($project->logo_path)->not->toBeNull();
    Storage::assertExists($project->logo_path);
});

test('project logo must be square and large enough', function () {
    Storage::fake();

    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), [
            'name' => 'Wide Logo',
            'tagline' => 'A one-liner.',
            'description' => 'Description body.',
            'category_id' => $category->id,
            'logo' => UploadedFile::fake()->image('wide.png', 400, 200),
        ])
        ->assertSessionHasErrors('logo');

    $this->actingAs($creator)
        ->post(route('projects.store'), [
            'name' => 'Tiny Logo',
            'tagline' => 'A one-liner.',
            'description' => 'Description body.',
            'category_id' => $category->id,
            'logo' => UploadedFile::fake()->image('tiny.png', 64, 64),
        ])
        ->assertSessionHasErrors('logo');
});

test('discover can filter by pricing and sort by launch date', function () {
    $creator = User::factory()->create(['username' => 'maker']);
    $free = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => 'Free Launch',
        'pricing' => ProjectPricing::Free,
        'launch_date' => '2026-01-01',
    ]);
    $paid = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => 'Paid Launch',
        'pricing' => ProjectPricing::Paid,
        'launch_date' => '2026-08-01',
    ]);
    Release::factory()->for($free)->create(['published_at' => now()]);
    Release::factory()->for($paid)->create(['published_at' => now()]);

    $this->get(route('discover', ['pricing' => ProjectPricing::Paid->value]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Discover/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.name', 'Paid Launch'));

    $this->get(route('discover', ['sort' => 'launch_date']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Discover/Index')
            ->where('projects.data.0.name', 'Paid Launch'));
});

test('public project show includes pricing logo and launch date', function () {
    Storage::fake();

    $creator = User::factory()->create(['username' => 'maker']);
    $logoPath = UploadedFile::fake()->image('logo.png', 256, 256)->store('project-logos');
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'name' => 'Registry Kit',
        'pricing' => ProjectPricing::OpenSource,
        'launch_date' => '2026-07-04',
        'logo_path' => $logoPath,
    ]);
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Projects/Show')
            ->where('project.pricing', ProjectPricing::OpenSource->value)
            ->where('project.pricing_label', 'Open Source')
            ->whereNot('project.logo_url', null)
            ->where('project.launch_date', '2026-07-04'));
});
