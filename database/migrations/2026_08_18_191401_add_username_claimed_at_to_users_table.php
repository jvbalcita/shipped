<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('username_claimed_at')->nullable()->after('username');
        });

        // Every existing creator chose their username at registration;
        // only provider sign-ups start unclaimed.
        DB::table('users')->whereNull('username_claimed_at')->update([
            'username_claimed_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username_claimed_at');
        });
    }
};
