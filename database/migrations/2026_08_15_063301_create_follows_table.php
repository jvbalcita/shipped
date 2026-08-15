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
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('followable_type');
            $table->unsignedBigInteger('followable_id');
            $table->timestamps();

            $table->unique(['user_id', 'followable_type', 'followable_id'], 'follows_user_target_uniq');
            $table->index(['followable_type', 'followable_id'], 'follows_target_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
