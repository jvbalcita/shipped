<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. CategorySeeder is production-safe
     * and always runs; the Demo Launches only exist to make local and
     * testing feel alive.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoLaunchSeeder::class);
        }
    }
}
