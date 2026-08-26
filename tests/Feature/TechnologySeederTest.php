<?php

use App\Models\Technology;
use Database\Seeders\TechnologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('technology seeder creates the curated stack vocabulary', function () {
    $this->seed(TechnologySeeder::class);

    $groups = Technology::query()
        ->selectRaw('stack_group, count(*) as total')
        ->groupBy('stack_group')
        ->pluck('total', 'stack_group');

    expect($groups->toArray())->toEqual([
        'database' => 6,
        'frontend' => 11,
        'infrastructure' => 12,
        'laravel_version' => 4,
        'package' => 23,
        'php_version' => 5,
    ]);
});

test('technology seeder is idempotent when run twice', function () {
    $this->seed(TechnologySeeder::class);
    $this->seed(TechnologySeeder::class);

    expect(Technology::query()->count())->toBe(61);
});
