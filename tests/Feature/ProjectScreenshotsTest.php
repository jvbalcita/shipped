<?php

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('store attaches up to five screenshots', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $files = [
        UploadedFile::fake()->image('one.jpg'),
        UploadedFile::fake()->image('two.png'),
    ];

    $response = $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload($category, [
            'name' => 'My App',
            'screenshots' => $files,
            'screenshots_captions' => ['First shot', 'Second shot'],
        ]));

    $response->assertSessionHasNoErrors();

    $project = Project::where('name', 'My App')->first();

    expect($project->screenshots)->toHaveCount(2);
    expect($project->screenshots()->first()->caption)->toBe('First shot');
    expect(Storage::disk('public')->exists($project->screenshots()->first()->path))->toBeTrue();
});

test('store rejects a sixth screenshot', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $files = array_map(fn () => UploadedFile::fake()->image('shot.jpg'), range(1, 6));

    $response = $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'My App',
            'tagline' => 'A tagline',
            'description' => 'A description',
            'category_id' => $category->id,
            'screenshots' => $files,
        ]);

    $response->assertSessionHasErrors('screenshots');
    expect(Project::where('name', 'My App')->exists())->toBeFalse();
});

test('store rejects oversized screenshot', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'My App',
            'tagline' => 'A tagline',
            'description' => 'A description',
            'category_id' => $category->id,
            'screenshots' => [UploadedFile::fake()->create('big.png', 6000)],
        ]);

    $response->assertSessionHasErrors('screenshots.0');
});

test('update reorders and removes existing screenshots', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $first = $project->screenshots()->create(['path' => 'screenshots/a.jpg', 'sort_order' => 0]);
    $second = $project->screenshots()->create(['path' => 'screenshots/b.jpg', 'sort_order' => 1]);
    $third = $project->screenshots()->create(['path' => 'screenshots/c.jpg', 'sort_order' => 2]);

    $response = $this->actingAs($user)
        ->patch(route('projects.update', $project), [
            'screenshot_order' => [$second->id, $first->id, $third->id],
            'screenshot_captions' => [$first->id => 'Updated caption'],
            'removed_screenshots' => [$third->id],
        ]);

    $response->assertSessionHasNoErrors();

    expect($project->refresh()->screenshots)->toHaveCount(2);
    expect($second->refresh()->sort_order)->toBe(0);
    expect($first->refresh()->sort_order)->toBe(1);
    expect($first->refresh()->caption)->toBe('Updated caption');
    Storage::disk('public')->assertMissing('screenshots/c.jpg');
});

test('public show page renders the screenshot gallery', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $project->screenshots()->create(['path' => 'screenshots/g.jpg', 'caption' => 'Gallery', 'sort_order' => 0]);

    $response = $this->actingAs($user)
        ->get(route('projects.show', ['creator' => $user->username, 'project' => $project->slug]))
        ->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->where('project.screenshots.0.caption', 'Gallery'));
});
