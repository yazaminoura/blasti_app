<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Columns every page and every scheduled task filters on (upcoming departures, unpaid tickets, reminders). */
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->index(['date_depart', 'heure_depart'], 'voyages_depart_index');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['statut', 'date_depart'], 'reservations_statut_depart_index');
            $table->index(['statut', 'created_at'], 'reservations_statut_created_index');
            $table->index('retour_de');
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropIndex('voyages_depart_index');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_statut_depart_index');
            $table->dropIndex('reservations_statut_created_index');
            $table->dropIndex(['retour_de']);
        });
    }
};
