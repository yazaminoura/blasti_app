<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "update_num_siege" trigger decremented autocars.nbr_siege on every reservation.
 * nbr_siege is the bus CAPACITY (used to draw the seat map and shared by every voyage
 * of the bus), so each booking permanently shrank the bus. Availability is computed
 * from the reservations table instead, so the trigger is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS update_num_siege');
        }
    }

    public function down(): void
    {
        // Intentionally not re-created.
    }
};
