<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Observation keys let the vocabulary declare which dependency
     * declarations in a public repository evidence the technology:
     * literal package names, or "name:constraint" for version groups.
     */
    public function up(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->json('observation_keys')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->dropColumn('observation_keys');
        });
    }
};
