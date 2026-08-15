<?php

use App\Models\Category;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder creates the full curated category set', function () {
    $this->seed(DatabaseSeeder::class);

    $expected = [
        'SaaS',
        'Developer Tool',
        'Open Source',
        'Game',
        'Experiment',
        'Package',
        'Library',
        'Plugin',
        'Theme',
        'Mobile App',
        'Desktop App',
        'AI Tool',
        'Boilerplate',
        'Course',
        'Community',
    ];

    expect(Category::query()->pluck('name')->all())->toEqualCanonicalizing($expected);
    expect(Category::query()->count())->toBe(15);
});
