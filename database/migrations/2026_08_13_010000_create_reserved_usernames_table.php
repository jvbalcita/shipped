<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserved_usernames', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at', 'reserved_usernames_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_usernames');
    }
};
