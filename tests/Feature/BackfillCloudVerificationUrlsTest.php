<?php

use App\Models\CloudConnection;
use App\Models\ConnectedEnvironment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function legacyCloudProject(array $domains, array $projectAttributes = []): Project
{
    $creator = User::factory()->create();
    $connection = CloudConnection::factory()->for($creator)->create();
    $environment = ConnectedEnvironment::factory()->for($connection)->create([
        'domains' => $domains,
    ]);

    return Project::factory()->for($creator, 'creator')->create(array_merge([
        // Legacy shape: token-backed verification with no URL evidence.
        'connected_environment_id' => $environment->id,
        'is_public' => true,
        'verification_status' => 'verified',
        'verified_at' => now()->subWeek(),
        'laravel_cloud_url' => null,
        'verification_method' => null,
    ], $projectAttributes));
}

test('a dry run reports an unambiguous legacy project without writing anything', function () {
    $project = legacyCloudProject(['example.com', 'my-app-main.laravel.cloud']);

    $this->artisan('shipped:backfill-cloud-verification-urls')
        ->expectsOutputToContain('Dry-run complete: considered 1 legacy project(s), backfilled 1, manual required 0')
        ->assertSuccessful();

    expect($project->fresh()->laravel_cloud_url)->toBeNull()
        ->and($project->fresh()->verification_method)->toBeNull();
});

test('apply backfills the single Cloud hostname but withdraws verification until rechecked', function () {
    $verifiedAt = now()->subWeek()->startOfSecond();
    $project = legacyCloudProject(['My-App-Main.Laravel.Cloud.'], [
        'verified_at' => $verifiedAt,
        'verification_checked_at' => now()->subDay(),
    ]);

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true])
        ->expectsOutputToContain('Apply complete: considered 1 legacy project(s), backfilled 1, manual required 0')
        ->assertSuccessful();

    $fresh = $project->fresh();

    expect($fresh->laravel_cloud_url)->toBe('https://my-app-main.laravel.cloud')
        ->and($fresh->verification_method)->toBe('cloud_url')
        ->and($fresh->verification_status)->toBe('unverified')
        ->and($fresh->verified_at)->toBeNull()
        ->and($fresh->is_public)->toBeFalse()
        ->and($fresh->verification_failure_reason)->toBe('Legacy verification requires a Laravel Cloud URL recheck.');
});

test('apply reruns are no-ops for already-backfilled projects', function () {
    $project = legacyCloudProject(['my-app-main.laravel.cloud']);

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true])->assertSuccessful();

    $firstRun = $project->fresh();

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true])
        ->expectsOutputToContain('considered 0 legacy project(s), backfilled 0')
        ->assertSuccessful();

    expect($project->fresh()->laravel_cloud_url)->toBe($firstRun->laravel_cloud_url);
});

test('a custom domain coexisting with exactly one Cloud hostname still backfills', function () {
    $project = legacyCloudProject(['example.com', 'my-app-main.laravel.cloud', 'www.example.com']);

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true])->assertSuccessful();

    expect($project->fresh()->laravel_cloud_url)->toBe('https://my-app-main.laravel.cloud');
});

test('legacy projects with zero Cloud hostnames require manual migration', function () {
    $project = legacyCloudProject(['example.com']);

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true])
        ->expectsOutputToContain('Apply complete: considered 1 legacy project(s), backfilled 0, manual required 1')
        ->expectsOutputToContain("Manual required for 1 project(s): creator must paste their Cloud URL. IDs: {$project->id}")
        ->assertSuccessful();

    expect($project->fresh()->laravel_cloud_url)->toBeNull()
        ->and($project->fresh()->verification_status)->toBe('unverified')
        ->and($project->fresh()->is_public)->toBeFalse();
});

test('legacy projects with multiple Cloud hostnames are never guessed', function () {
    $project = legacyCloudProject(['alpha-main.laravel.cloud', 'beta-main.laravel.cloud']);

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true])
        ->expectsOutputToContain('manual required 1')
        ->assertSuccessful();

    expect($project->fresh()->laravel_cloud_url)->toBeNull()
        ->and($project->fresh()->verification_status)->toBe('unverified')
        ->and($project->fresh()->is_public)->toBeFalse();
});

test('projects without a connected environment are never considered', function () {
    $creator = User::factory()->create();
    Project::factory()->unverified()->for($creator, 'creator')->create();

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true])
        ->expectsOutputToContain('considered 0 legacy project(s)')
        ->assertSuccessful();
});

test('verify without apply refuses to run', function () {
    $this->artisan('shipped:backfill-cloud-verification-urls', ['--verify' => true])
        ->expectsOutputToContain('--verify requires --apply')
        ->assertFailed();
});

test('apply with verify rechecks backfilled projects through their URL', function () {
    Http::preventStrayRequests();
    Http::fake(['https://my-app-main.laravel.cloud' => Http::response('', 200)]);

    $project = legacyCloudProject(['my-app-main.laravel.cloud'], [
        'verification_status' => 'stale',
        'live_url' => 'https://my-app-main.laravel.cloud',
    ]);

    $this->artisan('shipped:backfill-cloud-verification-urls', ['--apply' => true, '--verify' => true])
        ->expectsOutputToContain('verified 1')
        ->assertSuccessful();

    expect($project->fresh()->verification_status)->toBe('verified')
        ->and($project->fresh()->verification_checked_at)->not->toBeNull();
});
