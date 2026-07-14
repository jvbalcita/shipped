<?php

test('project dependencies run before dependent tables', function () {
    $migrations = glob(dirname(__DIR__, 2).'/database/migrations/*.php');

    expect($migrations)->not->toBeFalse();

    sort($migrations);

    $projectMigration = '2026_07_10_224913_create_projects_table.php';
    $historicalCheersMigration = dirname(__DIR__, 2).'/database/migrations/2026_07_10_224913_create_cheers_table.php';
    $cheersMigration = array_values(array_filter(
        $migrations,
        fn (string $migration): bool => $migration !== $historicalCheersMigration
            && str_contains((string) file_get_contents($migration), "Schema::create('cheers'"),
    ))[0] ?? null;

    expect((string) file_get_contents($historicalCheersMigration))
        ->toContain("Schema::hasTable('projects')")
        ->and($cheersMigration)->not->toBeNull()
        ->and(array_search($projectMigration, array_map('basename', $migrations), true))
        ->toBeLessThan(array_search(basename($cheersMigration), array_map('basename', $migrations), true));
});
