<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table
                ->unsignedTinyInteger('profile_featured_order')
                ->nullable()
                ->after('is_public');
            $table->unique(
                ['user_id', 'profile_featured_order'],
                'projects_user_profile_featured_order_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique('projects_user_profile_featured_order_unique');
            $table->dropColumn('profile_featured_order');
        });
    }
};
