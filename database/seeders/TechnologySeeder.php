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
            [TechnologyGroup::LaravelVersion, [
                'Laravel 10',
                'Laravel 11',
                'Laravel 12',
                'Laravel 13',
            ]],
            [TechnologyGroup::PhpVersion, [
                'PHP 8.1',
                'PHP 8.2',
                'PHP 8.3',
                'PHP 8.4',
                'PHP 8.5',
            ]],
            [TechnologyGroup::Frontend, [
                'Blade',
                'Livewire',
                'Inertia',
                'Vue',
                'React',
                'Svelte',
                'Alpine.js',
                'htmx',
                'Tailwind CSS',
                'Bootstrap',
                'Vite',
            ]],
            [TechnologyGroup::Database, [
                'MySQL',
                'PostgreSQL',
                'MariaDB',
                'SQLite',
                'SQL Server',
                'MongoDB',
            ]],
            [TechnologyGroup::Infrastructure, [
                'Redis',
                'Meilisearch',
                'Algolia',
                'Amazon S3',
                'Cloudflare R2',
                'Amazon SQS',
                'Amazon SES',
                'Postmark',
                'Mailgun',
                'Resend',
                'Pusher',
                'Reverb',
            ]],
            [TechnologyGroup::Package, [
                'Filament',
                'Cashier',
                'Sanctum',
                'Passport',
                'Fortify',
                'Jetstream',
                'Breeze',
                'Socialite',
                'Scout',
                'Horizon',
                'Telescope',
                'Pulse',
                'Pest',
                'Saloon',
                'Nova',
                'Spark',
                'Laravel Debugbar',
                'Laravel Excel',
                'DomPDF',
                'Intervention Image',
                'Spatie Permission',
                'Spatie Media Library',
                'Spatie Backup',
            ]],
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
