<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     *   php artisan db:seed            adds the dummy data
     *   php artisan migrate:fresh --seed   empties the database and adds it again
     *
     * The dummy data (all codes start with DEMO-) can be removed with
     * `php artisan demo:data --remove`. See app/Console/Commands/DemoData.php.
     */
    public function run(): void
    {
        Artisan::call('demo:data', [], $this->command?->getOutput());
    }
}
