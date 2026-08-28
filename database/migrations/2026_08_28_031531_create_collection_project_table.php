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
        Schema::create('collection_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['collection_id', 'project_id'], 'collection_project_pair_unique');
            $table->index(['collection_id', 'position'], 'collection_project_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_project');
    }
};
