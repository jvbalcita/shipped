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
            $table->foreignId('connected_environment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('verification_checked_at')->nullable();
            $table->text('verification_failure_reason')->nullable();
            $table->boolean('is_demo')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('connected_environment_id');
            $table->dropColumn(['verification_checked_at', 'verification_failure_reason', 'is_demo']);
        });
    }
};
