<?php

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Services\LaravelCloud\ProjectVerificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function projectsHasLaravelCloudUrlUnique(): bool
{
    return collect(Schema::getIndexes('projects'))
        ->contains(fn (array $index): bool => $index['name'] === 'projects_laravel_cloud_url_unique'
            && ($index['unique'] ?? false) === true);
}

test('projects has a unique index on laravel_cloud_url that allows multiple nulls', function () {
    expect(projectsHasLaravelCloudUrlUnique())->toBeTrue();

    Project::factory()->count(2)->create(['laravel_cloud_url' => null]);

    expect(Project::query()->whereNull('laravel_cloud_url')->count())->toBe(2);
});

test('the unique Cloud origin migration collapses duplicates and keeps the loser in Studio', function () {
    $origin = 'https://shared-main.laravel.cloud';
    $creator = User::factory()->create();
    $stranger = User::factory()->create();

    if (projectsHasLaravelCloudUrlUnique()) {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique('projects_laravel_cloud_url_unique');
        });
    }

    $olderUnverified = Project::factory()->for($creator, 'creator')->create([
        'laravel_cloud_url' => $origin,
        'verification_status' => 'unverified',
        'is_public' => false,
        'verified_at' => null,
    ]);
    $winner = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => $origin,
        'verification_status' => 'verified',
        'is_public' => true,
        'verified_at' => now(),
    ]);
    Release::factory()->for($winner)->create(['published_at' => now()]);
    $loser = Project::factory()->public()->for($creator, 'creator')->create([
        'laravel_cloud_url' => $origin,
        'verification_status' => 'verified',
        'is_public' => true,
        'verified_at' => now(),
    ]);
    Release::factory()->for($loser)->create(['published_at' => now()]);

    expect($olderUnverified->id)->toBeLessThan($winner->id)
        ->and($winner->id)->toBeLessThan($loser->id);

    $migration = require database_path('migrations/2026_08_22_003100_add_unique_laravel_cloud_url_to_projects_table.php');
    $migration->up();

    expect(projectsHasLaravelCloudUrlUnique())->toBeTrue();

    expect($winner->fresh())
        ->laravel_cloud_url->toBe($origin)
        ->verification_status->toBe('verified')
        ->is_public->toBeTrue();

    foreach ([$olderUnverified, $loser] as $collapsed) {
        expect($collapsed->fresh())
            ->laravel_cloud_url->toBeNull()
            ->is_public->toBeFalse()
            ->verification_status->toBe('unverified')
            ->verified_at->toBeNull()
            ->verification_failure_reason->toBe(ProjectVerificationService::ORIGIN_ALREADY_USED);
    }

    $this->actingAs($creator)
        ->get(route('projects.edit', $loser))
        ->assertOk();

    $this->actingAs($stranger)
        ->get(route('projects.show', [$creator, $loser->fresh()]))
        ->assertNotFound();

    $this->get(route('badges.show', $loser->fresh()))->assertNotFound();
});

test('the unique Cloud origin migration treats empty strings as null instead of unique values', function () {
    if (projectsHasLaravelCloudUrlUnique()) {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique('projects_laravel_cloud_url_unique');
        });
    }

    $first = Project::factory()->create(['laravel_cloud_url' => null]);
    $second = Project::factory()->create(['laravel_cloud_url' => null]);

    DB::table('projects')->whereIn('id', [$first->id, $second->id])->update(['laravel_cloud_url' => '']);

    $migration = require database_path('migrations/2026_08_22_003100_add_unique_laravel_cloud_url_to_projects_table.php');
    $migration->up();

    expect($first->fresh()->laravel_cloud_url)->toBeNull()
        ->and($second->fresh()->laravel_cloud_url)->toBeNull()
        ->and(projectsHasLaravelCloudUrlUnique())->toBeTrue();
});
