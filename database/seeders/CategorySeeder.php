<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The curated category set. Safe to run in any environment:
     * idempotent via firstOrCreate on the unique slug.
     */
    public function run(): void
    {
        collect([
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
        ])->each(fn (string $name) => Category::query()->firstOrCreate(['slug' => str($name)->slug()], ['name' => $name]));
    }
}
