<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prices that go up as the departure gets closer (Admin > Paramètres > Tarifs selon la date), see App\Support\Tarif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            // [{"jours": 7, "type": "montant", "valeur": 10}, ...]
            $table->json('majorations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parametres', fn (Blueprint $table) => $table->dropColumn('majorations'));
    }
};
