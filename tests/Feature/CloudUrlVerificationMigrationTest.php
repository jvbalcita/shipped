<?php

use Illuminate\Support\Facades\Schema;

test('the cloud URL verification migration can be replayed after all additions exist', function () {
    Schema::table('projects', function ($table): void {
        $table->dropIndex('projects_verification_method_status_idx');
        $table->dropColumn('verification_method');
    });

    $migration = require database_path('migrations/2026_08_20_140022_add_cloud_url_verification_to_projects_table.php');

    $exception = null;

    try {
        $migration->up();
    } catch (Throwable $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeNull();
    expect(Schema::hasColumn('projects', 'laravel_cloud_url'))->toBeTrue()
        ->and(Schema::hasColumn('projects', 'verification_method'))->toBeTrue()
        ->and(collect(Schema::getIndexes('projects'))
            ->where('name', 'projects_verification_method_status_idx')
            ->count())->toBe(1);
});
