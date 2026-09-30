<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Counter sales (Guichet): who sold the ticket, and the Guichetier role may now create bookings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // staff member who sold the ticket at the counter (null = booked by the client online)
            $table->foreignId('vendu_par')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });

        $role = Role::where('slug', 'guichetier')->first();
        if ($role) {
            $role->permissions()->syncWithoutDetaching([
                Permission::firstOrCreate(['name' => 'reservations.create'], ['slug' => Str::slug('reservations.create')])->id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('reservations', fn (Blueprint $table) => $table->dropConstrainedForeignId('vendu_par'));
    }
};
