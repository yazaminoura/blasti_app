<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan db:seed: the base lists only (cities, payment modes...), or the whole Moroccan demo
     * when DEMO_DATA=true (never in production). First admin account: php artisan blasti:admin
     */
    public function run(): void
    {
        $this->call(config('safar.demo') && ! app()->isProduction() ? DemoDataSeeder::class : ReferenceSeeder::class);
    }
}
