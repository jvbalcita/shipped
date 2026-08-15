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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('verb');
            $table->timestamp('occurred_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('occurred_at', 'activities_occurred_at_idx');
            $table->index(['actor_type', 'actor_id'], 'activities_actor_idx');
            $table->index(['subject_type', 'subject_id'], 'activities_subject_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
