<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Promo codes (Admin > Promotions)
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('type', 12)->default('pourcentage'); // pourcentage | montant
            $table->decimal('valeur', 10, 2);
            $table->decimal('min_montant', 10, 2)->nullable();   // order total needed
            $table->date('debut')->nullable();
            $table->date('fin')->nullable();
            $table->unsignedInteger('max_utilisations')->nullable();
            $table->unsignedInteger('utilisations')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // discount of each ticket (promo code or return-trip discount): total = prix + frais - remise
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('mode_reglement_id')->constrained('promotions')->nullOnDelete();
            $table->decimal('remise', 10, 2)->default(0)->after('frais');
            // return trip: order of the outbound journey
            $table->string('retour_de', 20)->nullable()->after('commande');
        });

        // "Warn me when a seat frees up" on a full bus
        Schema::create('alertes_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 120);
            $table->unsignedBigInteger('arret_depart_id')->nullable();
            $table->unsignedBigInteger('arret_arrivee_id')->nullable();
            $table->timestamp('envoyee_le')->nullable();
            $table->timestamps();
            $table->unique(['voyage_id', 'email']);
        });

        // Reviews of the trip (one per ticket), shown as the company's rating
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('societe_id')->nullable()->constrained('societes')->nullOnDelete();
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->boolean('publie')->default(true);
            $table->timestamps();
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('avis_demande_le')->nullable()->after('presence_confirmee_le');
        });

        // Company space: a back-office account tied to one transport company only sees its buses and trips
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('societe_id')->nullable()->after('isadmin')->constrained('societes')->nullOnDelete();
        });

        // "Pay at an agency / payment point" mode: the client pays with his order code before a deadline
        Schema::table('mode_reglements', function (Blueprint $table) {
            $table->boolean('en_agence')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('mode_reglements', fn (Blueprint $table) => $table->dropColumn('en_agence'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('societe_id');
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('avis_demande_le');
        });
        Schema::dropIfExists('avis');
        Schema::dropIfExists('alertes_places');
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn(['remise', 'retour_de']);
        });
        Schema::dropIfExists('promotions');
    }
};
