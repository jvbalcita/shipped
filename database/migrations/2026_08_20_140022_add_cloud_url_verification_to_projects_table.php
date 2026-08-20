<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand phase of the Laravel Cloud URL verification rollout: add the
     * per-project Cloud origin and verification method next to the legacy
     * token-backed columns, which stay in place until the contract phase.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'laravel_cloud_url')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->string('laravel_cloud_url')->nullable()->after('live_url');
            });
        }

        if (! Schema::hasColumn('projects', 'verification_method')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->string('verification_method', 32)->nullable()->after('verification_status');
            });
        }

        $hasVerificationIndex = collect(Schema::getIndexes('projects'))
            ->contains(fn (array $index): bool => $index['name'] === 'projects_verification_method_status_idx');

        if (! $hasVerificationIndex) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->index(
                    ['verification_method', 'verification_status'],
                    'projects_verification_method_status_idx',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasVerificationIndex = collect(Schema::getIndexes('projects'))
            ->contains(fn (array $index): bool => $index['name'] === 'projects_verification_method_status_idx');

        if ($hasVerificationIndex) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropIndex('projects_verification_method_status_idx');
            });
        }

        if (Schema::hasColumn('projects', 'verification_method')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('verification_method');
            });
        }

        if (Schema::hasColumn('projects', 'laravel_cloud_url')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('laravel_cloud_url');
            });
        }
    }
};
