<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** php artisan db:seed: the Moroccan demo data (also loaded automatically by migrate on an empty database). */
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);
    }
}
