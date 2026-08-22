<?php

use App\Models\CloudConnection;
use App\Models\ConnectedEnvironment;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Services\LaravelCloud\CloudUrlProbeOutcome;
use App\Services\LaravelCloud\CloudUrlProbeResult;
use App\Services\LaravelCloud\LaravelCloudUrl;
use App\Services\LaravelCloud\LaravelCloudUrlProbe;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

test('a reachable Cloud URL verifies a project without publishing it and without calling the Cloud API', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 200)]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'is_public' => false,
        'live_url' => 'https://WWW.My-App-Main.Laravel.Cloud./launch?source=creator#top',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://My-App-Main.Laravel.Cloud/',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('verified')
        ->laravel_cloud_url->toBe('https://my-app-main.laravel.cloud')
        ->verification_method->toBe('cloud_url')
        ->is_public->toBeFalse()
        ->verified_at->not->toBeNull()
        ->verification_checked_at->not->toBeNull()
        ->verification_failure_reason->toBeNull();

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'cloud.laravel.com/api'));
});

test('a HEAD rejection falls back to a bounded GET when the origin answers 405', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::sequence([
        Http::response('', 405),
        Http::response('live', 200),
    ])]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh()->verification_status)->toBe('verified');

    Http::assertSentInOrder([
        fn (Request $request) => $request->method() === 'HEAD',
        fn (Request $request) => $request->method() === 'GET',
    ]);
});

test('a same-origin redirect is accepted as reachable deployment evidence', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://my-app-main.laravel.cloud' => Http::response('', 302, [
            'Location' => 'https://my-app-main.laravel.cloud/login',
        ]),
    ]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://my-app-main.com',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('verified')
        ->verification_failure_reason->toBeNull();
});

test('a cross-origin redirect remains rejected', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://my-app-main.laravel.cloud' => Http::response('', 302, [
            'Location' => 'https://another-project.com/login',
        ]),
    ]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://my-app-main.com',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('failed')
        ->verification_failure_reason->toBe('The Laravel Cloud URL rejected the verification request.');
});

test('a Cloud URL with a matching project name verifies against a custom live domain', function (string $cloudUrl) {
    Http::preventStrayRequests();
    Http::fake([$cloudUrl => Http::response('', 200)]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://artisanbizops.com',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => $cloudUrl,
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('verified')
        ->laravel_cloud_url->toBe($cloudUrl)
        ->verification_failure_reason->toBeNull();
})->with([
    'hyphenated Cloud slug' => 'https://artisan-bizops-x1233.laravel.cloud',
    'suffixed Cloud slug' => 'https://artisanbizops-app.laravel.cloud',
]);

test('a Cloud URL with only a project name prefix is rejected', function () {
    Http::preventStrayRequests();
    Http::fake(['https://artisanbizopsfake.laravel.cloud' => Http::response('', 200)]);

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://artisanbizops.com',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://artisanbizopsfake.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('failed')
        ->verification_failure_reason->toBe('The Laravel Cloud URL name does not match the project live URL name.');

    Http::assertSentCount(0);
});

test('a definitive Cloud response fails verification and withdraws the project', function (int $status) {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', $status)]);

    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => null,
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('failed')
        ->laravel_cloud_url->toBe('https://my-app-main.laravel.cloud')
        ->verification_failure_reason->toBe('The Laravel Cloud URL rejected the verification request.');
})->with([
    'not found' => [404],
    'unauthorized origin' => [401],
    'redirect to custom domain' => [301],
]);

test('a retryable Cloud outcome marks verification stale, withdraws the project, and keeps verified_at', function (callable $fakeResponse) {
    Http::preventStrayRequests();
    $fakeResponse();

    $creator = User::factory()->create();
    $verifiedAt = now()->subDay()->startOfSecond();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => null,
        'verified_at' => $verifiedAt,
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('stale')
        ->verified_at->equalTo($verifiedAt)->toBeTrue()
        ->verification_checked_at->not->toBeNull()
        ->and($project->fresh()->verification_failure_reason)->toBeString();
})->with([
    'server error' => [fn () => Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 503)])],
    'rate limited' => [fn () => Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 429)])],
    'timeout' => [fn () => Http::fake(['https://my-app-main.laravel.cloud' => Http::failedConnection('cURL error 28: Operation timed out')])],
    'connection refused' => [fn () => Http::fake(['https://my-app-main.laravel.cloud' => Http::failedConnection('cURL error 7: Connection refused')])],
]);

test('a successful retry restores verification without automatically republishing', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 200)]);

    $creator = User::factory()->create();
    $project = Project::factory()->stale()->for($creator, 'creator')->create([
        'is_public' => false,
        'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('verified')
        ->verification_failure_reason->toBeNull()
        ->is_public->toBeFalse();
});

test('verification rejects URLs outside the exact Cloud origin', function (string $url) {
    Http::preventStrayRequests();

    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create();

    $this->actingAs($creator)
        ->from(route('projects.edit', $project))
        ->post(route('projects.verification.store', $project), ['laravel_cloud_url' => $url])
        ->assertRedirect(route('projects.edit', $project))
        ->assertSessionHasErrors('laravel_cloud_url');

    expect($project->fresh()->verification_status)->toBe('unverified');
})->with([
    'http scheme' => 'http://my-app-main.laravel.cloud',
    'custom domain' => 'https://example.com',
    'multi-level cloud host' => 'https://foo.bar.laravel.cloud',
    'path' => 'https://my-app-main.laravel.cloud/health',
    'missing url' => '',
]);

test('only the project creator can verify a project', function () {
    $creator = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create(['laravel_cloud_url' => null]);

    $this->actingAs($intruder)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertForbidden();

    expect($project->fresh()->verification_status)->toBe('unverified');
});

test('the studio edit page exposes Cloud URL evidence without legacy connection data', function () {
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create(['api_token' => 'never-expose-this-token']);
    $environment = ConnectedEnvironment::factory()->for($connection)->create();
    $project = Project::factory()->verified()->for($creator, 'creator')->create([
        'connected_environment_id' => $environment->id,
        'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->actingAs($creator)
        ->get(route('projects.edit', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Edit')
            ->where('project.laravel_cloud_url', 'https://my-app-main.laravel.cloud')
            ->where('project.verification_status', 'verified')
            ->where('project.verification_method', 'cloud_url')
            ->missing('connectedEnvironments')
            ->missing('project.api_token'));
});

test('updating a live URL invalidates verification and public visibility', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
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
        ->laravel_cloud_url->toBe('https://my-app-main.laravel.cloud')
        ->verification_failure_reason->toBe('The live URL changed and must be verified again.');
});

test('changing the Laravel Cloud URL invalidates verification and public visibility', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://old-main.laravel.cloud',
        'verification_checked_at' => now()->subDay(),
    ]);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), ['laravel_cloud_url' => 'https://new-main.laravel.cloud'])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('unverified')
        ->verified_at->toBeNull()
        ->laravel_cloud_url->toBe('https://new-main.laravel.cloud')
        ->verification_failure_reason->toBe('The Laravel Cloud URL changed and must be verified again.');
});

test('clearing the Laravel Cloud URL invalidates verification and public visibility', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        'verification_checked_at' => now()->subDay(),
    ]);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), ['laravel_cloud_url' => null])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('unverified')
        ->laravel_cloud_url->toBeNull()
        ->verification_failure_reason->toBe('The Laravel Cloud URL changed and must be verified again.');
});

test('a canonically equivalent Cloud URL edit keeps verification intact', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        'verification_checked_at' => now()->subDay(),
    ]);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), ['laravel_cloud_url' => 'HTTPS://My-App-Main.Laravel.Cloud./'])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeTrue()
        ->verification_status->toBe('verified')
        ->laravel_cloud_url->toBe('https://my-app-main.laravel.cloud');
});

test('an unrelated edit does not disturb verification', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        'verification_checked_at' => now()->subDay(),
    ]);

    $this->actingAs($creator)
        ->patch(route('projects.update', $project), ['tagline' => 'A fresh one-liner.'])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->is_public->toBeTrue()
        ->verification_status->toBe('verified');
});

test('a reachable Cloud URL cannot verify a project with an unrelated live URL', function () {
    Http::preventStrayRequests();
    Http::fake(['https://other-app.laravel.cloud' => Http::response('', 200)]);

    $creator = User::factory()->create(['username' => 'creator']);
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://artisanbizops.com',
        'laravel_cloud_url' => null,
    ]);
    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://other-app.laravel.cloud',
        ])
        ->assertRedirect(route('projects.edit', $project));

    expect($project->fresh())
        ->verification_status->toBe('failed')
        ->is_public->toBeFalse()
        ->verification_failure_reason->toBe('The Laravel Cloud URL name does not match the project live URL name.');

    Http::assertSentCount(0);
});

test('an unverified project cannot become public even with a published release', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->unverified()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->actingAs($creator)
        ->patch(route('projects.visibility.update', $project), ['is_public' => true])
        ->assertSessionHasErrors('is_public');
});

test('the public launch page labels a verified project as live on Laravel Cloud', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->public()->for($creator, 'creator')->create();
    Release::factory()->for($project)->create(['published_at' => now()]);

    $this->get(route('projects.show', [$creator, $project]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Show')
            ->where('project.verification_status', 'verified'));

    expect((string) file_get_contents(resource_path('js/pages/Projects/Show.vue')))
        ->toContain('Live on Laravel Cloud')
        ->not->toContain('Verified Laravel Cloud');
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

test('verification probes are rate limited per creator and project', function () {
    $creator = User::factory()->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->mock(LaravelCloudUrlProbe::class, function (MockInterface $mock): void {
        $mock->shouldReceive('probe')->times(5)->andReturn(
            new CloudUrlProbeResult(CloudUrlProbeOutcome::Reachable, 200, null, 1),
        );
    });

    foreach (range(1, 5) as $_) {
        $this->actingAs($creator)
            ->post(route('projects.verification.store', $project), [
                'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
            ])
            ->assertRedirect();
    }

    $this->actingAs($creator)
        ->post(route('projects.verification.store', $project), [
            'laravel_cloud_url' => 'https://my-app-main.laravel.cloud',
        ])
        ->assertTooManyRequests();
});

test('the daily recheck withdraws legacy verified projects instead of skipping them', function () {
    Http::preventStrayRequests();

    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create();
    $project = Project::factory()->for($creator, 'creator')->create([
        'connected_environment_id' => $environment->id,
        'is_public' => true,
        'verification_status' => 'verified',
        'verified_at' => now()->subDay(),
        'laravel_cloud_url' => null,
    ]);

    $this->artisan('shipped:refresh-cloud-verifications')
        ->expectsOutputToContain('legacy pending 1')
        ->assertSuccessful();

    expect($project->fresh())
        ->is_public->toBeFalse()
        ->verification_status->toBe('unverified')
        ->verification_failure_reason->toBe('Legacy verification requires a Laravel Cloud URL recheck.')
        ->verification_checked_at->not->toBeNull();

    Http::assertSentCount(0);
});

test('the daily recheck probes URL-backed projects and reports aggregate counters', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://healthy-main.laravel.cloud' => Http::response('', 200),
        'https://broken-main.laravel.cloud' => Http::response('', 404),
        'https://flaky-main.laravel.cloud' => Http::response('', 503),
    ]);

    $creator = User::factory()->create();
    $healthy = Project::factory()->verified()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://healthy-main.laravel.cloud',
        'live_url' => 'https://healthy-main.laravel.cloud',
        'is_public' => true,
    ]);
    $broken = Project::factory()->verified()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://broken-main.laravel.cloud',
        'live_url' => 'https://broken-main.laravel.cloud',
        'is_public' => true,
    ]);
    $flakyVerifiedAt = now()->subDay()->startOfSecond();
    $flaky = Project::factory()->verified()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://flaky-main.laravel.cloud',
        'live_url' => 'https://flaky-main.laravel.cloud',
        'is_public' => true,
        'verified_at' => $flakyVerifiedAt,
    ]);
    $legacy = Project::factory()->unverified()->for($creator, 'creator')->create([
        'laravel_cloud_url' => null,
    ]);
    $demo = Project::factory()->verified()->demo()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://demo-main.laravel.cloud',
        'live_url' => 'https://demo-main.laravel.cloud',
        'is_public' => true,
    ]);

    $this->artisan('shipped:refresh-cloud-verifications')
        ->expectsOutputToContain('Rechecked 3 project(s): 1 verified, 1 failed, 1 stale, 0 exception(s)')
        ->assertSuccessful();

    expect($healthy->fresh())
        ->verification_status->toBe('verified')
        ->is_public->toBeTrue()
        ->and($broken->fresh())
        ->verification_status->toBe('failed')
        ->is_public->toBeFalse()
        ->and($flaky->fresh())
        ->verification_status->toBe('stale')
        ->is_public->toBeFalse()
        ->verified_at->equalTo($flakyVerifiedAt)->toBeTrue()
        ->and($legacy->fresh()->verification_checked_at)->toBeNull()
        ->and($demo->fresh()->verification_checked_at)->toBeNull();
});

test('the daily recheck continues after an unexpected per-project exception', function () {
    Http::preventStrayRequests();
    Http::fake(['https://healthy-main.laravel.cloud' => Http::response('', 200)]);

    $creator = User::factory()->create();
    $exploding = Project::factory()->verified()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://exploding-main.laravel.cloud',
        'live_url' => 'https://exploding-main.laravel.cloud',
    ]);
    $healthy = Project::factory()->verified()->for($creator, 'creator')->create([
        'laravel_cloud_url' => 'https://healthy-main.laravel.cloud',
        'live_url' => 'https://healthy-main.laravel.cloud',
    ]);

    $probe = app(LaravelCloudUrlProbe::class);

    $this->mock(LaravelCloudUrlProbe::class, function (MockInterface $mock) use ($probe): void {
        $mock->shouldReceive('probe')->andReturnUsing(
            fn (LaravelCloudUrl $url) => $url->host() === 'exploding-main.laravel.cloud'
                ? throw new RuntimeException('probe exploded')
                : $probe->probe($url),
        );
    });

    $this->artisan('shipped:refresh-cloud-verifications')
        ->expectsOutputToContain('Rechecked 1 project(s): 1 verified, 0 failed, 0 stale, 1 exception(s)')
        ->assertSuccessful();

    expect($healthy->fresh()->verification_status)->toBe('verified')
        ->and($exploding->fresh()->verification_status)->toBe('verified');
});

test('the daily recheck processes every chunk', function () {
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response('', 200)]);

    $creator = User::factory()->create();

    Project::factory()
        ->count(101)
        ->sequence(function ($sequence): array {
            $url = 'https://app-'.(($sequence->index % 50) + 1).'-main.laravel.cloud';

            return [
                'laravel_cloud_url' => $url,
                'live_url' => $url,
            ];
        })
        ->for($creator, 'creator')
        ->create();

    $this->artisan('shipped:refresh-cloud-verifications')
        ->expectsOutputToContain('Rechecked 101 project(s): 101 verified, 0 failed, 0 stale, 0 exception(s)')
        ->assertSuccessful();
});
