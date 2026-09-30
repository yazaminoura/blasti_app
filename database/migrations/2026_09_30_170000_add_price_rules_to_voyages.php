<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each voyage can have its own "closer departure = higher price" rules (App\Support\Tarif).
 * null = the default rules (Admin > Paramètres > Tarifs par défaut); [] = no increase for this voyage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->json('majorations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('voyages', fn (Blueprint $table) => $table->dropColumn('majorations'));
    }
};
