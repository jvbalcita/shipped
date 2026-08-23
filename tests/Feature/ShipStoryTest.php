<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\ShipStory;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

function completeShipStoryPayload(array $overrides = []): array
{
    return array_merge([
        'problem' => 'Small Laravel teams need a clear place to explain what they shipped.',
        'audience' => 'Indie Laravel builders looking for practical examples.',
        'shipped' => 'A public launch record with a durable project page.',
        'build_decisions' => 'We kept the first slice in Laravel, Inertia, and Vue so the workflow stayed cohesive.',
        'hardest_problem' => 'The hardest part was choosing a focused story instead of building another social feed.',
        'lessons_learned' => 'A useful return path starts with a story people can understand and share.',
        'next' => 'Invite a few creators to use the workflow and observe what they return to.',
    ], $overrides);
}

test('a project cannot enter public discovery until its Ship Story is approved', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->verified()->for($creator, 'creator')->create();
    $project->shipStory()->delete();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->actingAs($creator)
        ->from(route('projects.edit', $project))
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertRedirect(route('projects.edit', $project))
        ->assertSessionHasErrors([
            'is_public' => 'Complete and approve your Ship Story before making this project public.',
        ]);

    expect($project->fresh()->is_public)->toBeFalse();
});

test('a creator can save a private draft and explicitly approve a complete Ship Story', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->put(route('projects.ship-story.update', $project), [
            'problem' => 'A private draft can start with one honest sentence.',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->shipStory()->firstOrFail())
        ->problem->toBe('A private draft can start with one honest sentence.')
        ->approved_at->toBeNull();

    $this->actingAs($creator)
        ->put(route('projects.ship-story.update', $project), completeShipStoryPayload(['approve' => true]))
        ->assertRedirect(route('projects.edit', $project));

    $story = $project->shipStory()->firstOrFail();

    expect($story->isApprovedAndComplete())->toBeTrue()
        ->and($story->approved_at)->not->toBeNull();
});

test('the Studio edit page exposes the private Ship Story draft', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $project->shipStory()->create([
        'problem' => 'A draft stays in the Studio until its creator approves it.',
    ]);

    $this->actingAs($creator)
        ->get(route('projects.edit', $project))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Edit')
            ->where('shipStory.problem', 'A draft stays in the Studio until its creator approves it.')
            ->where('shipStory.is_approved', false)
            ->where('shipStory.is_complete', false));
});

test('approval requires every core Ship Story prompt', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $response = $this->actingAs($creator)
        ->put(route('projects.ship-story.update', $project), [
            'problem' => 'Only the problem has been drafted.',
            'approve' => true,
        ]);

    expect($response->status())->toBe(302);

    $errors = app('session.store')->get('errors.default.messages', []);

    expect(collect([
        'audience',
        'shipped',
        'build_decisions',
        'hardest_problem',
        'lessons_learned',
    ])->every(fn (string $field): bool => array_key_exists($field, $errors)))->toBeTrue();

    expect(ShipStory::query()->where('project_id', $project->id)->exists())->toBeFalse();
});

test('only the project creator can save its Ship Story', function () {
    $creator = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->actingAs($intruder)
        ->put(route('projects.ship-story.update', $project), completeShipStoryPayload())
        ->assertForbidden();

    expect(ShipStory::query()->where('project_id', $project->id)->exists())->toBeFalse();
});

test('an approved Ship Story is exposed on its public project page after filing', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->verified()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->actingAs($creator)
        ->put(route('projects.ship-story.update', $project), completeShipStoryPayload(['approve' => true]))
        ->assertRedirect(route('projects.edit', $project));

    $this->actingAs($creator)
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh()->isPubliclyDiscoverable())->toBeTrue();

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Show')
            ->where('project.ship_story.problem', 'Small Laravel teams need a clear place to explain what they shipped.')
            ->where('project.ship_story.audience', 'Indie Laravel builders looking for practical examples.'));

    $this->get(route('creators.show', $creator))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.0.ship_story_excerpt', 'Small Laravel teams need a clear place to explain what they shipped.'));

    $this->get(route('og.project', ['creator' => $creator, 'project' => $project]))
        ->assertSuccessful()
        ->assertSee('Small Laravel teams need a clear place to explain what they shipped.');
});

test('scheduled publication does not bypass Ship Story approval', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->verified()->for($creator, 'creator')->create();
    $project->shipStory()->delete();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $this->artisan('shipped:publish-scheduled-releases')->assertSuccessful();

    expect($project->fresh()->is_public)->toBeFalse();
});

test('the migration backfills a private Ship Story draft from legacy project data', function () {
    $project = Project::factory()->for(User::factory(), 'creator')->create([
        'description' => '<p>  A concise overview for an existing launch. </p>',
    ]);
    Release::factory()->for($project)->create([
        'notes' => 'The first release notes become the starting shipped summary.',
        'published_at' => now()->subDay(),
    ]);
    Release::factory()->for($project)->create([
        'notes' => 'A later release should not win the backfill.',
        'published_at' => now(),
    ]);

    Schema::dropIfExists('ship_stories');

    $migration = require database_path('migrations/2026_08_23_074755_create_ship_stories_table.php');
    $migration->up();

    $story = ShipStory::query()->whereBelongsTo($project)->firstOrFail();

    expect($story)
        ->problem->toBe('A concise overview for an existing launch.')
        ->audience->toBe('')
        ->shipped->toBe('The first release notes become the starting shipped summary.')
        ->approved_at->toBeNull();
});
