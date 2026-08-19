<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('category seeder creates the full curated category set', function () {
    $this->seed(CategorySeeder::class);

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

test('category seeder is idempotent when run twice', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::query()->count())->toBe(15);
});
