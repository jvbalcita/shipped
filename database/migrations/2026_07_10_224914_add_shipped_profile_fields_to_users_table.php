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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('title', 50)->default('Creator')->after('username');
            $table->string('location', 80)->nullable()->after('title');
            $table->string('bio', 280)->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('bio');
            $table->json('links')->nullable()->after('avatar_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'title', 'location', 'bio', 'avatar_path', 'links']);
        });
    }
};
