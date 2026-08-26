<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. CategorySeeder and
     * TechnologySeeder are production-safe and always run; the Demo
     * Launches only exist to make local and testing feel alive.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);
        $this->call(TechnologySeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoLaunchSeeder::class);
        }
    }
}
