<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder runs categories and demo launches outside production', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Category::query()->count())->toBe(15);
    expect(User::query()->where('email', 'studio@shipped.test')->exists())->toBeTrue();
});

test('database seeder skips demo launches in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    // db:seed asks for confirmation in production, so run it exactly
    // like the production runbook does.
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Category::query()->count())->toBe(15);
    expect(User::query()->where('email', 'studio@shipped.test')->exists())->toBeFalse();
});
