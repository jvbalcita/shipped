<?php

use App\Models\CloudConnection;
use App\Models\ConnectedEnvironment;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function environmentPayload(array $domains): array
{
    return [
        'data' => [
            'id' => 'env-1',
            'attributes' => ['name' => 'Production'],
            'relationships' => [
                'primaryDomain' => ['data' => ['id' => 'domain-1']],
            ],
        ],
        'included' => [
            ['id' => 'domain-1', 'attributes' => ['name' => $domains[0]]],
        ],
    ];
}

test('a matching Cloud hostname verifies a project but does not publish it', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create(['api_token' => 'secret-token']);
    $environment = ConnectedEnvironment::factory()->for($connection)->create([
        'application_id' => 'app-1',
        'environment_id' => 'env-1',
        'domains' => ['old.shipped.test'],
    ]);
    $project = Project::factory()->for($creator, 'creator')->create(['live_url' => 'https://shipped.test']);

    Http::fake([
        'https://cloud.laravel.com/api/environments/env-1*' => Http::response(environmentPayload(['shipped.test'])),
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'connected_environment_id' => $environment->id,
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('verified')
        ->is_public->toBeFalse()
        ->and($environment->fresh()->domains)->toBe(['shipped.test']);
});

test('verification matches a paginated custom Cloud domain and repairs the application label', function () {
    Http::preventStrayRequests();

    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create(['api_token' => 'secret-token']);
    $environment = ConnectedEnvironment::factory()->for($connection)->create([
        'application_id' => 'app-1',
        'application_name' => 'app-a1cef50f-b4a5-4ec4-a398-74f83dcc8f08',
        'environment_id' => 'env-1',
        'domains' => ['old.artisanbizops.com'],
    ]);
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://www.artisanbizops.com',
    ]);

    Http::fake([
        'https://cloud.laravel.com/api/environments/env-1?include=primaryDomain,application' => Http::response([
            'data' => [
                'id' => 'env-1',
                'attributes' => ['name' => 'main'],
                'relationships' => [
                    'application' => ['data' => ['id' => 'app-1']],
                ],
            ],
            'included' => [
                ['id' => 'app-1', 'type' => 'applications', 'attributes' => ['name' => 'Artisan Jack']],
            ],
        ]),
        'https://cloud.laravel.com/api/environments/env-1/domains' => Http::response([
            'data' => [
                ['id' => 'domain-1', 'attributes' => ['domain' => 'preview.artisanbizops.com']],
            ],
            'links' => [
                'next' => 'https://cloud.laravel.com/api/environments/env-1/domains?page=2',
            ],
        ]),
        'https://cloud.laravel.com/api/environments/env-1/domains?page=2' => Http::response([
            'data' => [
                ['id' => 'domain-2', 'attributes' => ['domain' => 'artisanbizops.com']],
            ],
            'links' => ['next' => null],
        ]),
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'connected_environment_id' => $environment->id,
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh()->verification_status)->toBe('verified')
        ->and($environment->fresh())
        ->application_name->toBe('Artisan Jack')
        ->domains->toBe([
            'preview.artisanbizops.com',
            'artisanbizops.com',
        ]);

    Http::assertSentCount(3);
});

test('a host mismatch fails verification and withdraws the project', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create([
        'application_id' => 'app-1',
        'environment_id' => 'env-1',
    ]);
    $project = Project::factory()->public()->for($creator, 'creator')->create(['live_url' => 'https://other.test']);

    Http::fake([
        'https://cloud.laravel.com/api/environments/env-1*' => Http::response(environmentPayload(['shipped.test'])),
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), ['connected_environment_id' => $environment->id])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('failed')
        ->verification_failure_reason->toBe('The live URL does not match the selected Laravel Cloud environment.');
});

test('an unavailable Cloud API marks verification as stale and withdraws the project', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create([
        'application_id' => 'app-1',
        'environment_id' => 'env-1',
    ]);
    $project = Project::factory()->public()->for($creator, 'creator')->create();

    Http::fake([
        'https://cloud.laravel.com/api/environments/env-1*' => Http::response([], 503),
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), ['connected_environment_id' => $environment->id])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('stale')
        ->verification_failure_reason->toBe('Laravel Cloud could not be reached.');
});

test('invalid Cloud credentials require reconnection and withdraw the project', function (int $status): void {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create([
        'application_id' => 'app-1',
        'environment_id' => 'env-1',
    ]);
    $project = Project::factory()->public()->for($creator, 'creator')->create();

    Http::fake([
        'https://cloud.laravel.com/api/environments/env-1*' => Http::response([], $status),
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), ['connected_environment_id' => $environment->id])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('failed')
        ->verification_failure_reason->toBe('Laravel Cloud credentials are invalid. Reconnect Cloud and verify again.');
})->with([401, 403]);

test('verification rejects an environment owned by another creator', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();
    $environment = ConnectedEnvironment::factory()->create();

    $this->actingAs($creator)
        ->from(route('projects.edit', $project))
        ->post(route('projects.verification.store', $project), ['connected_environment_id' => $environment->id])
        ->assertRedirect(route('projects.edit', $project))
        ->assertSessionHasErrors('connected_environment_id');
});

test('updating a live URL invalidates verification and public visibility', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'live_url' => 'https://before.test',
        'verification_checked_at' => now()->subDay(),
    ]);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), ['live_url' => 'https://after.test'])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('unverified')
        ->verified_at->toBeNull()
        ->verification_failure_reason->toBe('The live URL changed and must be verified again.');
});

test('updating the selected environment invalidates verification and public visibility', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $currentEnvironment = ConnectedEnvironment::factory()->for($connection)->create();
    $replacementEnvironment = ConnectedEnvironment::factory()->for($connection)->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'connected_environment_id' => $currentEnvironment->id,
    ]);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), ['connected_environment_id' => $replacementEnvironment->id])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('unverified')
        ->verified_at->toBeNull()
        ->verification_failure_reason->toBe('The selected Laravel Cloud environment changed and must be verified again.');
});

test('an unverified project cannot become public even with a published release', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->unverified()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->actingAs($creator)
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertSessionHasErrors('is_public');
});

test('only verified published projects are discoverable to guests', function () {
    $creator = User::factory()->create(['username' => 'creator']);
    $verified = Project::factory()->public()->for($creator, 'creator')->create(['name' => 'Verified']);
    Release::factory()->for($verified)->create(['published_at' => now()]);
    $unverified = Project::factory()->unverified()->for($creator, 'creator')->create([
        'name' => 'Unverified',
        'is_public' => true,
    ]);
    Release::factory()->for($unverified)->create(['published_at' => now()]);

    $this->get(route('discover'))->assertSuccessful()->assertSee('Verified')->assertDontSee('Unverified');
    $this->get(route('creators.show', $creator))->assertSuccessful()->assertSee('Verified')->assertDontSee('Unverified');
    $this->get(route('projects.show', ['creator' => $creator, 'project' => $unverified]))->assertNotFound();
});

test('daily rechecks invalidate a connection and withdraw every bound project when its token is invalid', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create(['api_token' => 'invalid-token']);
    $environment = ConnectedEnvironment::factory()->for($connection)->create([
        'application_id' => 'app-1',
        'environment_id' => 'env-1',
    ]);
    $firstProject = Project::factory()->public()->for($creator, 'creator')->create([
        'connected_environment_id' => $environment->id,
    ]);
    $secondProject = Project::factory()->verified()->for($creator, 'creator')->create([
        'connected_environment_id' => $environment->id,
    ]);

    Http::fake([
        'https://cloud.laravel.com/api/environments/env-1*' => Http::response([], 401),
    ]);

    $this->artisan('shipped:refresh-cloud-verifications')->assertSuccessful();

    expect($connection->fresh())
        ->status->toBe('invalid')
        ->and($firstProject->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('failed')
        ->and($secondProject->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('failed');
});

test('daily rechecks make retryable failures stale without clearing verification evidence and continue', function () {
    $unavailableCreator = User::factory()->create();
    $unavailableConnection = CloudConnection::factory()->for($unavailableCreator)->create(['api_token' => 'unavailable-token']);
    $unavailableEnvironment = ConnectedEnvironment::factory()->for($unavailableConnection)->create([
        'application_id' => 'app-unavailable',
        'environment_id' => 'env-unavailable',
    ]);
    $verifiedAt = now()->subDay()->startOfSecond();
    $unavailableProject = Project::factory()->public()->for($unavailableCreator, 'creator')->create([
        'connected_environment_id' => $unavailableEnvironment->id,
        'verified_at' => $verifiedAt,
    ]);

    $validCreator = User::factory()->create();
    $validConnection = CloudConnection::factory()->for($validCreator)->create(['api_token' => 'valid-token']);
    $validEnvironment = ConnectedEnvironment::factory()->for($validConnection)->create([
        'application_id' => 'app-valid',
        'environment_id' => 'env-valid',
    ]);
    $validProject = Project::factory()->for($validCreator, 'creator')->create([
        'connected_environment_id' => $validEnvironment->id,
        'live_url' => 'https://valid.shipped.test',
    ]);

    Http::fake([
        'https://cloud.laravel.com/api/environments/env-unavailable*' => Http::response([], 503),
        'https://cloud.laravel.com/api/environments/env-valid*' => Http::response(environmentPayload(['valid.shipped.test'])),
    ]);

    $this->artisan('shipped:refresh-cloud-verifications')->assertSuccessful();

    expect($unavailableProject->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('stale')
        ->verified_at->toEqual($verifiedAt)
        ->and($validProject->fresh()->verification_status)->toBe('verified');
});
