<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Built With row carries two independent assertions: the creator's
     * declaration (is_declared) and the system's observation from the
     * project's public repository (observed_at, provenance observed).
     */
    public function up(): void
    {
        Schema::table('project_technology', function (Blueprint $table) {
            $table->boolean('is_declared')->default(true)->after('project_id');
            $table->timestamp('observed_at')->nullable()->after('provenance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_technology', function (Blueprint $table) {
            $table->dropColumn(['is_declared', 'observed_at']);
        });
    }
};
