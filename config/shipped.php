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

    'username_reservation_days' => 30,

    'username_change_cooldown_minutes' => 10080,

    // Emergency-only escape hatch for db:wipe / migrate:fresh-class
    // commands in production. Seeding never needs this: db:seed is not
    // a prohibited command and DatabaseSeeder is production-safe.
    'allow_destructive_commands' => env('SHIPPED_ALLOW_DESTRUCTIVE_COMMANDS', false),
];
