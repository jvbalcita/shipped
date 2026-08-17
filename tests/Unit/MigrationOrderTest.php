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

test('the canonical cheers migration creates the polymorphic schema directly', function () {
    $migration = file_get_contents(
        dirname(__DIR__, 2).'/database/migrations/2026_07_14_005855_create_cheers_table_after_projects.php',
    );

    expect($migration)
        ->not->toBeFalse()
        ->toContain('$table->morphs(\'cheerable\', \'cheers_cheerable_idx\')')
        ->toContain('$table->unique([\'user_id\', \'cheerable_type\', \'cheerable_id\'], \'cheers_user_cheerable_unq\')')
        ->not->toContain('$table->foreignId(\'project_id\')');
});

test('the polymorphic cheers migration remains safe after a partial mysql alter', function () {
    $migration = file_get_contents(
        dirname(__DIR__, 2).'/database/migrations/2026_08_13_204554_make_cheers_polymorphic.php',
    );
    $dropForeignPosition = strpos($migration, '$table->dropForeign([\'project_id\'])');
    $dropUniquePosition = strpos($migration, '$table->dropUnique([\'project_id\', \'user_id\'])');

    expect($migration)
        ->not->toBeFalse()
        ->not->toContain("|| Schema::hasColumn('cheers', 'cheerable_id')")
        ->and(substr_count($migration, "Schema::table('cheers'"))->toBeGreaterThanOrEqual(3)
        ->and($dropForeignPosition)->not->toBeFalse()
        ->and($dropUniquePosition)->not->toBeFalse()
        ->and($dropForeignPosition)->toBeLessThan($dropUniquePosition);
});
