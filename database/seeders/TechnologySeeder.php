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
     * The curated stack vocabulary, grouped by stack group, with the
     * observation keys that let a public repository evidence each
     * technology. Keys are literal dependency names, or
     * "name:constraint" for version technologies; a constraint key is
     * observed when the declared constraint's floor still satisfies it.
     * Technologies without keys (Blade, SQL databases) cannot be read
     * from manifests and stay creator-declared. Safe to run in any
     * environment: the vocabulary is seeder-owned and idempotent.
     */
    public function run(): void
    {
        $vocabulary = [
            [TechnologyGroup::LaravelVersion, [
                'Laravel 10' => ['laravel/framework:>=10.0,<11.0'],
                'Laravel 11' => ['laravel/framework:>=11.0,<12.0'],
                'Laravel 12' => ['laravel/framework:>=12.0,<13.0'],
                'Laravel 13' => ['laravel/framework:>=13.0,<14.0'],
            ]],
            [TechnologyGroup::PhpVersion, [
                'PHP 8.1' => ['php:>=8.1,<8.2'],
                'PHP 8.2' => ['php:>=8.2,<8.3'],
                'PHP 8.3' => ['php:>=8.3,<8.4'],
                'PHP 8.4' => ['php:>=8.4,<8.5'],
                'PHP 8.5' => ['php:>=8.5,<8.6'],
            ]],
            [TechnologyGroup::Frontend, [
                'Blade' => null,
                'Livewire' => ['livewire/livewire'],
                'Inertia' => [
                    '@inertiajs/vue3',
                    '@inertiajs/inertia-vue3',
                    '@inertiajs/react',
                    '@inertiajs/inertia-react',
                    '@inertiajs/inertia-laravel',
                ],
                'Vue' => ['vue'],
                'React' => ['react'],
                'Svelte' => ['svelte'],
                'Alpine.js' => ['alpinejs'],
                'htmx' => ['htmx.org'],
                'Tailwind CSS' => ['tailwindcss'],
                'Bootstrap' => ['bootstrap'],
                'Vite' => ['vite'],
            ]],
            [TechnologyGroup::Database, [
                'MySQL' => null,
                'PostgreSQL' => null,
                'MariaDB' => null,
                'SQLite' => null,
                'SQL Server' => null,
                'MongoDB' => ['mongodb/laravel-mongodb'],
            ]],
            [TechnologyGroup::Infrastructure, [
                'Redis' => ['predis/predis'],
                'Meilisearch' => ['meilisearch/laravel-scout'],
                'Algolia' => ['algolia/algoliasearch-client-php', 'laravel/scout-algolia'],
                'Amazon S3' => ['league/flysystem-aws-s3-v3'],
                'Cloudflare R2' => null,
                'Amazon SQS' => null,
                'Amazon SES' => ['aws/aws-sdk-php'],
                'Postmark' => ['symfony/postmark-mailer'],
                'Mailgun' => ['symfony/mailgun-mailer'],
                'Resend' => ['resend/resend-php'],
                'Pusher' => ['pusher/pusher-php-server'],
                'Reverb' => ['laravel/reverb'],
            ]],
            [TechnologyGroup::Package, [
                'Filament' => ['filament/filament'],
                'Cashier' => ['laravel/cashier'],
                'Sanctum' => ['laravel/sanctum'],
                'Passport' => ['laravel/passport'],
                'Fortify' => ['laravel/fortify'],
                'Jetstream' => ['laravel/jetstream'],
                'Breeze' => ['laravel/breeze'],
                'Socialite' => ['laravel/socialite'],
                'Scout' => ['laravel/scout'],
                'Horizon' => ['laravel/horizon'],
                'Telescope' => ['laravel/telescope'],
                'Pulse' => ['laravel/pulse'],
                'Pest' => ['pestphp/pest'],
                'Saloon' => ['saloonphp/saloon'],
                'Nova' => ['laravel/nova'],
                'Spark' => ['laravel/spark'],
                'Laravel Debugbar' => ['barryvdh/laravel-debugbar'],
                'Laravel Excel' => ['maatwebsite/excel'],
                'DomPDF' => ['barryvdh/laravel-dompdf'],
                'Intervention Image' => ['intervention/image'],
                'Spatie Permission' => ['spatie/laravel-permission'],
                'Spatie Media Library' => ['spatie/laravel-medialibrary'],
                'Spatie Backup' => ['spatie/laravel-backup'],
            ]],
        ];

        foreach ($vocabulary as [$group, $technologies]) {
            foreach ($technologies as $name => $observationKeys) {
                Technology::query()->updateOrCreate(
                    ['slug' => str($name)->slug()],
                    [
                        'name' => $name,
                        'stack_group' => $group->value,
                        'observation_keys' => $observationKeys,
                    ],
                );
            }
        }
    }
}
