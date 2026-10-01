<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The first prices were FLOAT (about 6 digits on MySQL: 12 345,67 DH is stored wrong, cents drift);
     * every newer money column is DECIMAL(10,2). Existing values are rounded to the cent.
     */
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->decimal('prix', 10, 2)->change();
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('prix', 10, 2)->change();
            $table->decimal('frais', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->float('prix')->change();
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->float('prix')->change();
            $table->float('frais')->change();
        });
    }
};
