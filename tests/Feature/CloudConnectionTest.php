<?php

use App\Models\Category;
use App\Models\CloudConnection;
use App\Models\ConnectedEnvironment;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Services\LaravelCloud\Exceptions\CloudApiUnavailable;
use App\Services\LaravelCloud\Exceptions\InvalidCloudToken;
use App\Services\LaravelCloud\HostnameNormalizer;
use App\Services\LaravelCloud\LaravelCloudClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

test('a creator owns one cloud connection and projects select connected environments', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'connected_environment_id' => $environment->id,
        'verification_status' => 'verified',
        'verified_at' => now(),
    ]);

    expect($creator->cloudConnection->is($connection))->toBeTrue()
        ->and($project->connectedEnvironment->is($environment))->toBeTrue();
});

test('public discovery requires a verified project with a published release', function () {
    $creator = User::factory()->create();
    $visible = Project::factory()->public()->for($creator, 'creator')->create(['name' => 'Verified launch']);
    Release::factory()->for($visible)->create(['published_at' => now()->subMinute()]);

    $unverified = Project::factory()->public()->unverified()->for($creator, 'creator')->create(['name' => 'Unverified launch']);
    Release::factory()->for($unverified)->create(['published_at' => now()->subMinute()]);

    $unreleased = Project::factory()->public()->for($creator, 'creator')->create(['name' => 'Unreleased launch']);

    expect(Project::query()->public()->pluck('name')->all())->toBe(['Verified launch'])
        ->and($visible->isPubliclyDiscoverable())->toBeTrue()
        ->and($unverified->isPubliclyDiscoverable())->toBeFalse()
        ->and($unreleased->isPubliclyDiscoverable())->toBeFalse();
});

test('non-production discovery includes demo launches without treating them as verified', function () {
    $creator = User::factory()->create();
    $demo = Project::factory()->demo()->for($creator, 'creator')->create([
        'name' => 'Demo launch',
        'is_public' => true,
        'verification_status' => 'unverified',
    ]);
    Release::factory()->for($demo)->create(['published_at' => now()->subMinute()]);

    $this->get(route('discover'))
        ->assertSuccessful()
        ->assertSee('Demo launch');

    expect($demo->is_demo)->toBeTrue()
        ->and($demo->verification_status)->toBe('unverified')
        ->and($demo->isPubliclyDiscoverable())->toBeFalse();
});

test('production discovery excludes demo launches', function () {
    app()->instance('env', 'production');

    $creator = User::factory()->create();
    $demo = Project::factory()->demo()->for($creator, 'creator')->create([
        'name' => 'Production-hidden demo',
        'is_public' => true,
        'verification_status' => 'unverified',
    ]);
    Release::factory()->for($demo)->create(['published_at' => now()->subMinute()]);

    $this->get(route('discover'))
        ->assertSuccessful()
        ->assertDontSee('Production-hidden demo');
});

test('guests cannot directly access an unverified demo project in production', function () {
    app()->instance('env', 'production');

    $creator = User::factory()->create();
    $demo = Project::factory()->demo()->for($creator, 'creator')->create([
        'is_public' => true,
        'verification_status' => 'unverified',
    ]);
    Release::factory()->for($demo)->create(['published_at' => now()->subMinute()]);

    $verified = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($verified)->create(['published_at' => now()->subMinute()]);

    $this->get(route('projects.show', ['creator' => $creator, 'project' => $demo]))
        ->assertNotFound();
    $this->get(route('projects.show', ['creator' => $creator, 'project' => $verified]))
        ->assertSuccessful();
});

test('the demo seeder marks local launches as demo without faking verification', function () {
    $this->seed();

    $demo = Project::query()->where('slug', 'northstar')->firstOrFail();

    expect($demo->is_demo)->toBeTrue()
        ->and($demo->verification_status)->toBe('unverified')
        ->and($demo->isPubliclyDiscoverable())->toBeFalse();
});

test('withdrawing a project removes it from the public registry', function () {
    $project = Project::factory()->public()->create();
    Release::factory()->for($project)->create(['published_at' => now()->subMinute()]);

    $project->withdrawFromPublicRegistry();

    expect($project->fresh()->is_public)->toBeFalse()
        ->and($project->fresh()->isPubliclyDiscoverable())->toBeFalse();
});

test('Laravel Cloud client maps applications from the read-only API', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications' => Http::response([
            'data' => [
                ['id' => 'app-1', 'attributes' => ['name' => 'Shipped']],
            ],
        ]),
    ]);

    expect(app(LaravelCloudClient::class)->listApplications('secret-token'))
        ->toBe([['id' => 'app-1', 'name' => 'Shipped']]);

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && $request->url() === 'https://cloud.laravel.com/api/applications'
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Authorization', 'Bearer secret-token');
    });
});

test('Laravel Cloud client traverses all application pages', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications' => Http::response([
            'data' => [
                ['id' => 'app-1', 'attributes' => ['name' => 'First application']],
            ],
            'links' => ['next' => 'https://cloud.laravel.com/api/applications?page=2'],
        ]),
        'https://cloud.laravel.com/api/applications?page=2' => Http::response([
            'data' => [
                ['id' => 'app-2', 'attributes' => ['name' => 'Second application']],
            ],
            'links' => ['next' => null],
        ]),
    ]);

    expect(app(LaravelCloudClient::class)->listApplications('secret-token'))
        ->toBe([
            ['id' => 'app-1', 'name' => 'First application'],
            ['id' => 'app-2', 'name' => 'Second application'],
        ]);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://cloud.laravel.com/api/applications'));
});

test('Laravel Cloud client maps environment domains from primary and vanity domains', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications/app-1/environments?include=primaryDomain' => Http::response([
            'data' => [
                [
                    'id' => 'environment-1',
                    'attributes' => ['name' => 'production', 'vanity_domain' => 'vanity.shipped.test'],
                ],
            ],
        ]),
        'https://cloud.laravel.com/api/environments/environment-1?include=primaryDomain,application' => Http::response([
            'data' => [
                'id' => 'environment-1',
                'attributes' => ['name' => 'production', 'vanity_domain' => 'vanity.shipped.test'],
                'relationships' => [
                    'primaryDomain' => ['data' => ['id' => 'domain-1']],
                    'application' => ['data' => ['id' => 'app-1']],
                ],
            ],
            'included' => [
                ['id' => 'domain-1', 'attributes' => ['name' => 'shipped.test']],
                ['id' => 'app-1', 'type' => 'applications', 'attributes' => ['name' => 'Shipped']],
            ],
        ]),
    ]);

    $environments = app(LaravelCloudClient::class)->listEnvironments('secret-token', 'app-1');

    expect($environments)->toHaveCount(1)
        ->and($environments[0]->applicationId)->toBe('app-1')
        ->and($environments[0]->environmentId)->toBe('environment-1')
        ->and($environments[0]->applicationName)->toBe('Shipped')
        ->and($environments[0]->environmentName)->toBe('production')
        ->and($environments[0]->domains)->toBe(['shipped.test', 'vanity.shipped.test']);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://cloud.laravel.com/api/'));
});

test('Laravel Cloud client traverses all environment pages', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications/app-1/environments?include=primaryDomain' => Http::response([
            'data' => [
                ['id' => 'environment-1', 'attributes' => ['name' => 'production']],
            ],
            'links' => [
                'next' => 'https://cloud.laravel.com/api/applications/app-1/environments?include=primaryDomain&page=2',
            ],
        ]),
        'https://cloud.laravel.com/api/applications/app-1/environments?include=primaryDomain&page=2' => Http::response([
            'data' => [
                ['id' => 'environment-2', 'attributes' => ['name' => 'staging']],
            ],
            'links' => ['next' => null],
        ]),
        'https://cloud.laravel.com/api/environments/environment-1?include=primaryDomain,application' => Http::response([
            'data' => ['id' => 'environment-1', 'attributes' => ['name' => 'production']],
        ]),
        'https://cloud.laravel.com/api/environments/environment-2?include=primaryDomain,application' => Http::response([
            'data' => ['id' => 'environment-2', 'attributes' => ['name' => 'staging']],
        ]),
    ]);

    $environments = app(LaravelCloudClient::class)->listEnvironments('secret-token', 'app-1');

    expect($environments)->toHaveCount(2)
        ->and($environments[0]->environmentId)->toBe('environment-1')
        ->and($environments[1]->environmentId)->toBe('environment-2');

    Http::assertSentCount(4);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://cloud.laravel.com/api/'));
});

test('hostname matching accepts a www equivalent but not a subdomain', function () {
    expect(HostnameNormalizer::matches(
        'https://www.shipped.test/pricing?source=registry',
        ['shipped.test'],
    ))->toBeTrue()
        ->and(HostnameNormalizer::matches('https://preview.shipped.test', ['shipped.test']))->toBeFalse()
        ->and(HostnameNormalizer::normalize('HTTPS://WWW.Shipped.Test.:8443/path'))->toBe('shipped.test')
        ->and(HostnameNormalizer::matches('https://shipped.test..', ['shipped.test']))->toBeFalse()
        ->and(HostnameNormalizer::normalize('not a hostname'))->toBeNull();
});

test('Laravel Cloud client rejects invalid tokens without exposing them', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications' => Http::response([], 401),
    ]);

    expect(fn () => app(LaravelCloudClient::class)->listApplications('secret-token'))
        ->toThrow(InvalidCloudToken::class, 'Laravel Cloud token is invalid.')
        ->not->toThrow('secret-token');
});

test('Laravel Cloud client marks unavailable API failures as retryable', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications' => Http::response([], 503),
    ]);

    expect(fn () => app(LaravelCloudClient::class)->listApplications('secret-token'))
        ->toThrow(CloudApiUnavailable::class, 'Laravel Cloud API is temporarily unavailable.');
});

test('Laravel Cloud client rejects forbidden tokens', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications' => Http::response([], 403),
    ]);

    expect(fn () => app(LaravelCloudClient::class)->listApplications('secret-token'))
        ->toThrow(InvalidCloudToken::class, 'Laravel Cloud token is invalid.');
});

test('Laravel Cloud client marks rate limits as retryable', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications' => Http::response([], 429),
    ]);

    expect(fn () => app(LaravelCloudClient::class)->listApplications('secret-token'))
        ->toThrow(CloudApiUnavailable::class, 'Laravel Cloud API is temporarily unavailable.');
});

test('Laravel Cloud client marks connection failures as retryable', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://cloud.laravel.com/api/applications' => Http::failedConnection(),
    ]);

    expect(fn () => app(LaravelCloudClient::class)->listApplications('secret-token'))
        ->toThrow(CloudApiUnavailable::class, 'Laravel Cloud API is temporarily unavailable.');
});

test('creating or replacing a cloud connection token over http is rejected', function () {
    $creator = User::factory()->create();

    $create = $this->actingAs($creator)
        ->post('/cloud-connection', ['api_token' => 'cloud-token']);

    expect($create->status())->toBe(405)
        ->and(CloudConnection::query()->where('user_id', $creator->id)->exists())->toBeFalse();

    $connection = CloudConnection::factory()->for($creator)->create(['api_token' => 'existing-token']);

    $replace = $this->actingAs($creator)
        ->post('/cloud-connection', ['api_token' => 'replacement-token']);

    expect($replace->status())->toBe(405)
        ->and($connection->fresh()->api_token)->toBe('existing-token');
});

test('disconnect withdraws affected projects and deletes cloud evidence', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create();
    $affected = Project::factory()->public()->for($creator, 'creator')->create([
        'connected_environment_id' => $environment->id,
        'verification_checked_at' => now()->subDay(),
    ]);
    $unaffected = Project::factory()->public()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->delete(route('cloud-connection.destroy'))
        ->assertRedirect(route('dashboard'));

    expect($connection->fresh())->toBeNull()
        ->and($environment->fresh())->toBeNull()
        ->and($affected->fresh()->only([
            'is_public',
            'verification_status',
            'verified_at',
            'verification_failure_reason',
        ]))->toBe([
            'is_public' => false,
            'verification_status' => 'unverified',
            'verified_at' => null,
            'verification_failure_reason' => 'Laravel Cloud connection removed.',
        ])
        ->and($affected->fresh()->verification_checked_at)->not->toBeNull()
        ->and($unaffected->fresh()->is_public)->toBeTrue();
});

test('the studio exposes only the creators safe connection metadata and environment choices', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create(['api_token' => 'never-expose-this']);
    $environment = ConnectedEnvironment::factory()->for($connection)->create();
    $otherConnection = CloudConnection::factory()->create();
    ConnectedEnvironment::factory()->for($otherConnection)->create();

    $this->actingAs($creator)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->has('cloudConnection', fn (Assert $connectionProp) => $connectionProp
                ->where('status', 'connected')
                ->where('last_validated_at', $connection->last_validated_at->toISOString())
                ->where('environment_count', 1)
                ->missing('api_token')
            )
            ->has('connectedEnvironments', 1)
            ->where('connectedEnvironments.0.id', $environment->id)
            ->where('connectedEnvironments.0.environment_name', $environment->environment_name)
            ->missing('connectedEnvironments.0.api_token')
        );

    $this->actingAs($creator)
        ->get(route('cloud-connection.environments'))
        ->assertSuccessful()
        ->assertJsonPath('0.id', $environment->id)
        ->assertJsonCount(1)
        ->assertJsonMissing(['api_token' => 'never-expose-this']);
});

test('the studio exposes actionable draft metadata without loading release records', function () {
    $creator = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Developer Tool']);
    $draft = Project::factory()->for($creator, 'creator')->for($category)->create([
        'name' => 'Draft with context',
        'tagline' => 'A draft that explains what comes next.',
        'verification_status' => 'unverified',
    ]);
    Release::factory()->count(2)->for($draft)->create();

    $this->actingAs($creator)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->has('projects', 1)
            ->where('projects.0.slug', (string) $draft->slug)
            ->where('projects.0.category.name', 'Developer Tool')
            ->where('projects.0.releases_count', 2)
            ->where('projects.0.verification_status', 'unverified')
            ->missing('projects.0.releases')
        );
});
