<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Used to load the demo data here, but later migrations add columns the demo needs (users.societe_id...):
     * it now loads once ALL migrations are done (DemoDataSeeder::chargerSiVide, AppServiceProvider).
     */
    public function up(): void
    {
    }

    public function down(): void
    {
    }
};
