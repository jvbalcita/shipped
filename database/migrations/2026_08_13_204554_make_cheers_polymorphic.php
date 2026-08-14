<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the cheers table from project-scoped to polymorphic so the same
     * Cheer model can target any "cheerable" (projects, comments, …). Existing
     * project cheers are preserved by backfilling the morph columns.
     *
     * This is intentionally forwards-only: comment cheers cannot be mapped back
     * to project_id, so the down() does not reverse the migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cheers') || Schema::hasColumn('cheers', 'cheerable_id')) {
            return;
        }

        Schema::table('cheers', function (Blueprint $table) {
            $table->string('cheerable_type')->nullable()->after('user_id');
            $table->unsignedBigInteger('cheerable_id')->nullable()->after('cheerable_type');
        });

        DB::table('cheers')
            ->whereNotNull('project_id')
            ->update([
                'cheerable_type' => 'project',
                'cheerable_id' => DB::raw('project_id'),
            ]);

        Schema::table('cheers', function (Blueprint $table) {
            $table->string('cheerable_type')->nullable(false)->change();
            $table->unsignedBigInteger('cheerable_id')->nullable(false)->change();

            $table->dropUnique(['project_id', 'user_id']);
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');

            $table->unique(['user_id', 'cheerable_type', 'cheerable_id'], 'cheers_user_cheerable_unq');
            $table->index(['cheerable_type', 'cheerable_id'], 'cheers_cheerable_idx');
        });
    }

    public function down(): void
    {
        // Forwards-only: comment cheers cannot be restored to a project_id column.
    }
};
