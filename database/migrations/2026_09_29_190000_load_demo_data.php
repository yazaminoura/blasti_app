<?php

use App\Models\Voyage;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fills an empty database with the demo data as soon as it is migrated (fresh clone:
     * "php artisan migrate" is enough). Skipped when there are already voyages, in tests,
     * or when DEMO_DATA=false in .env.
     */
    public function up(): void
    {
        if (! config('safar.demo') || app()->runningUnitTests() || Voyage::query()->exists()) {
            return;
        }

        (new DemoDataSeeder())->run();
    }

    public function down(): void
    {
        // demo rows are ordinary data: nothing to undo automatically (use migrate:fresh to start over)
    }
};
