<?php

use App\Enums\ProductEventName;
use App\Enums\TechnologyProvenance;
use App\Models\Category;
use App\Models\ProductEvent;
use App\Models\Project;
use App\Models\Release;
use App\Models\Technology;
use App\Models\User;
use App\Services\GitHub\Exceptions\GitHubApiUnavailable;
use App\Services\GitHub\StackObservationService;
use Database\Seeders\TechnologySeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    config(['services.github.app_token' => null]);
    $this->seed(TechnologySeeder::class);
    $this->creator = User::factory()->create();
});

function observeProject(Project $project): void
{
    app(StackObservationService::class)->observe($project);
}

function composerJson(array $overrides = []): string
{
    return json_encode(array_merge([
        'require' => [
            'php' => '^8.4',
            'laravel/framework' => '^13.0',
            'laravel/horizon' => '^5.0',
            'predis/predis' => '^3.0',
        ],
        'require-dev' => [
            'pestphp/pest' => '^4.0',
        ],
    ], $overrides));
}

function fakeRepository(string $composer, ?string $packageJson = null, int $status = 200): void
{
    Http::preventStrayRequests();
    Http::fake([
        'api.github.com/repos/*/contents/composer.json' => Http::response($composer, $status),
        'api.github.com/repos/*/contents/package.json' => Http::response(
            $packageJson ?? '{"devDependencies": {"vite": "^7.0.0", "vue": "^3.5.0"}}',
            $status,
        ),
    ]);
}

test('observation writes observed rows for the technologies the repository evidences', function () {
    fakeRepository(composerJson());

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    observeProject($project);

    $observed = $project->technologies()->wherePivot('provenance', 'observed')->pluck('name');

    expect($observed->values()->all())->toEqualCanonicalizing([
        'Laravel 13', 'PHP 8.4', 'Horizon', 'Redis', 'Pest', 'Vite', 'Vue',
    ]);
    expect($project->technologies()->wherePivotNotNull('observed_at')->count())->toBe(7);
    expect($project->technologies()->wherePivot('is_declared', false)->count())->toBe(7);
});

test('a creator declaration the repository confirms becomes observed while staying declared', function () {
    fakeRepository(composerJson());

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);
    $laravel = Technology::query()->where('name', 'Laravel 13')->firstOrFail();
    $project->technologies()->attach($laravel->getKey(), [
        'provenance' => 'declared',
        'is_declared' => true,
    ]);

    observeProject($project);

    $pivot = $project->technologies()->whereKey($laravel->getKey())->first()->pivot;

    expect($pivot->provenance)->toBe(TechnologyProvenance::Observed)
        ->and($pivot->is_declared)->toBeTrue()
        ->and($pivot->observed_at)->not->toBeNull();
});

test('an observation the repository stops supporting falls back or disappears', function () {
    fakeRepository(composerJson());

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);
    $laravel = Technology::query()->where('name', 'Laravel 12')->firstOrFail();
    $react = Technology::query()->where('name', 'React')->firstOrFail();
    $project->technologies()->attach([
        $laravel->getKey() => ['provenance' => 'declared', 'is_declared' => true],
        $react->getKey() => ['provenance' => 'observed', 'is_declared' => false, 'observed_at' => now()],
    ]);

    // The repository evidences Laravel 13 — the Laravel 12 declaration
    // falls back to plain declared and the React-only observation goes.
    fakeRepository(composerJson([
        'require' => [
            'php' => '^8.4',
            'laravel/framework' => '^13.0',
        ],
    ]));

    observeProject($project);

    $laravelRow = $project->technologies()->whereKey($laravel->getKey())->first();

    expect($laravelRow)->not->toBeNull()
        ->and($laravelRow->pivot->provenance)->toBe(TechnologyProvenance::Declared)
        ->and($laravelRow->pivot->observed_at)->toBeNull();

    expect($project->technologies()->whereKey($react->getKey())->exists())->toBeFalse();
});

test('a missing composer.json fails without touching existing records', function () {
    fakeRepository('{"require": {}}', status: 404);

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);
    $technology = Technology::query()->where('name', 'Vue')->firstOrFail();
    $project->technologies()->attach($technology->getKey(), [
        'provenance' => 'declared',
        'is_declared' => true,
    ]);

    $result = app(StackObservationService::class)->observe($project);

    expect($result->succeeded())->toBeFalse()
        ->and($result->failureReason?->value)->toBe('composer_json_missing')
        ->and($project->technologies()->count())->toBe(1)
        ->and($project->fresh()->verification_status)->toBe('unverified');
});

test('an unreadable composer.json is reported as invalid', function () {
    fakeRepository('not-json{{{');

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    $result = app(StackObservationService::class)->observe($project);

    expect($result->failureReason?->value)->toBe('composer_json_invalid');
});

test('a non-GitHub URL fails without any outbound request', function () {
    Http::preventStrayRequests();

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://gitlab.com/acme/rocket',
    ]);

    $result = app(StackObservationService::class)->observe($project);

    expect($result->failureReason?->value)->toBe('repo_url_invalid');
    Http::assertNothingSent();
});

test('a rate limited GitHub surfaces as an API outage', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.github.com/*' => Http::response(['message' => 'API rate limit exceeded'], 403),
    ]);

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    expect(fn () => observeProject($project))->toThrow(GitHubApiUnavailable::class);
});

test('the observation authenticates with the app token when one is configured', function () {
    config(['services.github.app_token' => 'shipped-app-token']);
    fakeRepository(composerJson());

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    observeProject($project);

    Http::assertSent(fn (Request $request): bool => $request->header('Authorization') !== null);
});

test('a creator can observe a project stack from the studio and the evidence is recorded', function () {
    fakeRepository(composerJson());

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    actingAs($this->creator)
        ->post(route('projects.stack-observation.store', $project))
        ->assertRedirect();

    expect($project->technologies()->wherePivot('provenance', 'observed')->count())->toBe(7);

    $names = ProductEvent::query()->where('subject_id', $project->getKey())->pluck('name')->all();

    expect($names)->toContain('stack_observation_started')
        ->and($names)->toContain('stack_observed')
        ->and($names)->not->toContain('stack_observation_failed');
});

test('observing records a failed event when the repository cannot be read', function () {
    fakeRepository('{}', status: 404);

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    actingAs($this->creator)
        ->post(route('projects.stack-observation.store', $project))
        ->assertSessionHasErrors('github');

    $names = ProductEvent::query()->where('subject_id', $project->getKey())->pluck('name')->all();

    expect($names)->toContain('stack_observation_failed')
        ->and($names)->not->toContain('stack_observed')
        ->and(ProductEventName::StackObservationFailed->canBeRecordedByClient())->toBeFalse();
});

test('only the project owner may observe a stack', function () {
    Http::preventStrayRequests();

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    actingAs(User::factory()->create())
        ->post(route('projects.stack-observation.store', $project))
        ->assertForbidden();

    expect(ProductEvent::query()->where('name', 'stack_observation_started')->exists())->toBeFalse();
});

test('a guest observing a stack is redirected to login', function () {
    Http::preventStrayRequests();

    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    $this->post(route('projects.stack-observation.store', $project))
        ->assertRedirect(route('login'));

    expect(ProductEvent::query()->where('name', 'stack_observation_started')->exists())->toBeFalse();
});

test('the scheduled command observes discoverable projects only', function () {
    fakeRepository(composerJson());

    Release::factory()->for(Project::factory()->for($this->creator, 'creator')->public()->create([
        'github_url' => 'https://github.com/acme/discoverable',
    ]))->create(['published_at' => now()]);

    Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/private-draft',
    ]);

    $this->artisan('shipped:observe-project-stacks')->assertSuccessful();

    $discoverable = Project::query()->where('github_url', 'https://github.com/acme/discoverable')->firstOrFail();
    $private = Project::query()->where('github_url', 'https://github.com/acme/private-draft')->firstOrFail();

    expect($discoverable->technologies()->wherePivot('provenance', 'observed')->count())->toBe(7)
        ->and($private->technologies()->count())->toBe(0);
});

test('the scheduled command skips demo launches', function () {
    Http::preventStrayRequests();

    Project::factory()->for($this->creator, 'creator')->demo()->create([
        'github_url' => 'https://github.com/acme/demo',
    ]);

    $this->artisan('shipped:observe-project-stacks')->assertSuccessful();

    Http::assertNothingSent();
});

test('saving the studio form keeps observed-only rows the picker does not select', function () {
    Storage::fake();
    fakeRepository(composerJson());

    $category = Category::query()->first() ?? Category::factory()->create();
    $project = Project::factory()->for($this->creator, 'creator')->create([
        'github_url' => 'https://github.com/acme/rocket',
    ]);

    observeProject($project);

    expect($project->technologies()->wherePivot('provenance', 'observed')->count())->toBe(7);

    actingAs($this->creator)
        ->from(route('projects.edit', $project))
        ->put(route('projects.update', $project), validProjectPayload($category, [
            'name' => $project->name,
            'technologies' => ['vue'],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $project->refresh();

    // The creator now declares Vue; the other six observed rows must
    // survive without becoming creator declarations.
    expect($project->technologies()->count())->toBe(7)
        ->and($project->technologies()->wherePivot('is_declared', true)->pluck('slug')->all())->toBe(['vue'])
        ->and($project->technologies()->wherePivot('provenance', 'observed')->count())->toBe(7);
});
