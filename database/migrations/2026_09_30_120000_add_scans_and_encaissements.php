<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // first time a controller scanned the ticket, and who let the traveller in
            $table->timestamp('scanne_le')->nullable()->after('embarque_le');
            $table->foreignId('embarque_par')->nullable()->after('scanne_le')->constrained('users')->nullOnDelete();
        });

        // every scan at the bus door, valid or refused (a ticket shown on two buses shows up here)
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('voyage_id')->nullable()->constrained()->nullOnDelete(); // bus being checked, if chosen
            $table->string('resultat', 30);
            $table->timestamps();
        });

        // money collected by a controller or at the counter: the cash drawer of each staff member
        Schema::create('encaissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('mode', 20); // especes | carte
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encaissements');
        Schema::dropIfExists('scans');
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('embarque_par');
            $table->dropColumn('scanne_le');
        });
    }
};
