<?php

use App\Enums\ProductEventName;
use App\Models\Category;
use App\Models\ProductEvent;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;

test('a guest cannot record product events', function () {
    $this->post(route('product-events.store'), ['name' => 'share_text_copied'])
        ->assertRedirect(route('login'));

    expect(ProductEvent::query()->count())->toBe(0);
});

test('an unknown event name is rejected', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('product-events.store'), ['name' => 'definitely_not_an_event'])
        ->assertInvalid('name');

    expect(ProductEvent::query()->count())->toBe(0);
});

test('server-side lifecycle events cannot be recorded from the client', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('product-events.store'), ['name' => 'verification_passed'])
        ->assertInvalid('name');

    expect(ProductEvent::query()->count())->toBe(0);
});

test('a kit asset copy is recorded with its actor and project subject', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->post(route('product-events.store'), [
            'name' => 'share_text_copied',
            'project_id' => $project->id,
        ])
        ->assertCreated();

    $event = ProductEvent::query()->sole();

    expect($event)
        ->name->toBe('share_text_copied')
        ->creator_id->toBe($creator->id)
        ->subject_type->toBe('project')
        ->subject_id->toBe($project->id)
        ->properties->toBeNull();
});

test('a share intent click records its network', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->post(route('product-events.store'), [
            'name' => 'share_intent_clicked',
            'project_id' => $project->id,
            'network' => 'x',
        ])
        ->assertCreated();

    expect(ProductEvent::query()->sole()->properties)->toBe(['network' => 'x']);
});

test('an unknown share network is rejected', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('product-events.store'), [
            'name' => 'share_intent_clicked',
            'network' => 'mastodon',
        ])
        ->assertInvalid('network');
});

test('another creators project cannot be attached to an event', function () {
    $creator = User::factory()->create();
    $foreignProject = Project::factory()->for(User::factory(), 'creator')->create();

    $this->actingAs($creator)
        ->post(route('product-events.store'), [
            'name' => 'share_text_copied',
            'project_id' => $foreignProject->id,
        ])
        ->assertInvalid('project_id');

    expect(ProductEvent::query()->count())->toBe(0);
});

test('a missing project id records an actor-only event', function () {
    $creator = User::factory()->create();

    $this->actingAs($creator)
        ->post(route('product-events.store'), ['name' => 'share_text_copied'])
        ->assertCreated();

    $event = ProductEvent::query()->sole();

    expect($event)
        ->creator_id->toBe($creator->id)
        ->subject_type->toBeNull()
        ->subject_id->toBeNull();
});

test('the endpoint is rate limited', function () {
    $creator = User::factory()->create();

    for ($attempt = 0; $attempt < 60; $attempt++) {
        $this->actingAs($creator)
            ->post(route('product-events.store'), ['name' => 'share_text_copied'])
            ->assertCreated();
    }

    $this->actingAs($creator)
        ->post(route('product-events.store'), ['name' => 'share_text_copied'])
        ->assertTooManyRequests();

    expect(ProductEvent::query()->count())->toBe(60);
});

test('creating a project records submission_started', function () {
    Storage::fake();
    $creator = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($creator)
        ->post(route('projects.store'), validProjectPayload($category, [
            'name' => 'Measured from the start',
            'tagline' => 'The first funnel step is observable.',
            'description' => 'Creating the private record starts the funnel.',
        ]))
        ->assertRedirect();

    $project = Project::query()->where('name', 'Measured from the start')->sole();

    expect(ProductEvent::query()->where('name', 'submission_started')->sole())
        ->creator_id->toBe($creator->id)
        ->subject_id->toBe($project->id);
});

test('filing a project records submission_published once', function () {
    // The factory gives verified projects a complete, approved Ship Story;
    // only the public release is missing before filing.
    $creator = User::factory()->create();
    $project = Project::factory()->verified()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $file = fn () => $this->actingAs($creator)
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertRedirect();

    $file();
    expect(ProductEvent::query()->where('name', 'submission_published')->count())->toBe(1);

    $file();
    expect(ProductEvent::query()->where('name', 'submission_published')->count())->toBe(1);
});

test('a creator-initiated verification records started and passed', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 200)]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://My-App-Main.Laravel.Cloud/',
        ])
        ->assertRedirect();

    $names = ProductEvent::query()->orderBy('id')->pluck('name')->all();

    expect($names)->toBe([
        ProductEventName::VerificationStarted->value,
        ProductEventName::VerificationPassed->value,
    ]);
});

test('approving a complete ship story records ship_story_published once', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    $payload = [
        'problem' => 'Why this exists.',
        'audience' => 'Who it serves.',
        'shipped' => 'What was shipped.',
        'build_decisions' => 'How it was built.',
        'hardest_problem' => 'The hard part.',
        'lessons_learned' => 'What was learned.',
        'next' => 'What is next.',
        'approve' => '1',
    ];

    $this->actingAs($creator)
        ->put(route('projects.ship-story.update', $project), $payload)
        ->assertRedirect();

    $this->actingAs($creator)
        ->put(route('projects.ship-story.update', $project), $payload)
        ->assertRedirect();

    expect(ProductEvent::query()->where('name', 'ship_story_published')->count())->toBe(1);
});
