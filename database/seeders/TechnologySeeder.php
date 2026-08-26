<?php

namespace Database\Seeders;

use App\Enums\TechnologyGroup;
use App\Models\Technology;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The curated stack vocabulary, grouped by stack group. Safe to run
     * in any environment: idempotent via firstOrCreate on the unique slug.
     */
    public function run(): void
    {
        $vocabulary = [
            [TechnologyGroup::LaravelVersion, ['Laravel 11', 'Laravel 12', 'Laravel 13']],
            [TechnologyGroup::PhpVersion, ['PHP 8.2', 'PHP 8.3', 'PHP 8.4', 'PHP 8.5']],
            [TechnologyGroup::Frontend, ['Blade', 'Livewire', 'Vue', 'React', 'Alpine.js', 'htmx', 'Tailwind CSS']],
            [TechnologyGroup::Database, ['MySQL', 'PostgreSQL', 'MariaDB', 'SQLite', 'SQL Server']],
            [TechnologyGroup::Infrastructure, ['Redis', 'Meilisearch', 'Algolia', 'Amazon S3', 'Reverb']],
            [TechnologyGroup::Package, ['Filament', 'Cashier', 'Sanctum', 'Scout', 'Horizon', 'Telescope', 'Pest', 'Saloon']],
        ];

        foreach ($vocabulary as [$group, $names]) {
            foreach ($names as $name) {
                Technology::query()->firstOrCreate(
                    ['slug' => str($name)->slug()],
                    ['name' => $name, 'stack_group' => $group->value],
                );
            }
        }
    }
}
