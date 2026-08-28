<?php

return [
    'suggested_tags' => [
        'laravel',
        'vue',
        'api',
        'indie',
        'open-source',
        'tailwind',
        'pest',
        'inertia',
    ],

    // The "Commonly used" suggestion row under the Packages search in
    // the stack picker; names must exist in the TechnologySeeder
    // vocabulary or they are silently dropped.
    'suggested_packages' => [
        'Filament',
        'Cashier',
        'Sanctum',
        'Pest',
        'Laravel Debugbar',
        'Spatie Permission',
    ],

    'username_reservation_days' => 30,

    'username_change_cooldown_minutes' => 10080,

    // User IDs allowed to curate Collections. Editorial curation is a
    // manual, single-operator role until the roadmap justifies roles.
    'curators' => array_map(
        'intval',
        array_filter(explode(',', (string) env('SHIPPED_CURATORS', ''))),
    ),

    // Emergency-only escape hatch for db:wipe / migrate:fresh-class
    // commands in production. Seeding never needs this: db:seed is not
    // a prohibited command and DatabaseSeeder is production-safe.
    'allow_destructive_commands' => env('SHIPPED_ALLOW_DESTRUCTIVE_COMMANDS', false),
];
