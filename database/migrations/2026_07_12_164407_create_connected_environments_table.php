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
        Schema::create('connected_environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloud_connection_id')->constrained()->cascadeOnDelete();
            $table->string('application_id');
            $table->string('environment_id');
            $table->string('application_name');
            $table->string('environment_name');
            $table->json('domains');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['cloud_connection_id', 'environment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connected_environments');
    }
};
