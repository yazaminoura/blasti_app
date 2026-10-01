<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reservation life cycle: en_attente (card payment in progress) -> confirmee -> annulee.
     * A cancelled reservation stays in the history but frees its seat: the unique index is on
     * (voyage_id, siege_actif), and siege_actif is emptied on cancellation (NULLs never collide).
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('statut', 20)->default('confirmee')->after('num_siege');
            $table->unsignedInteger('siege_actif')->nullable()->after('statut');
            $table->timestamp('paye_le')->nullable();
            $table->string('paiement_ref', 64)->nullable();
            $table->timestamp('annulee_le')->nullable();
            $table->string('annulee_par', 10)->nullable();
            $table->timestamp('rembourse_le')->nullable();
            $table->timestamp('rappel_envoye_le')->nullable();
        });

        DB::table('reservations')->update(['siege_actif' => DB::raw('num_siege')]);

        Schema::table('reservations', function (Blueprint $table) {
            // new index first: on MySQL the voyage_id foreign key needs an index starting with voyage_id at all times
            $table->unique(['voyage_id', 'siege_actif'], 'reservations_voyage_active_seat_unique');
            $table->dropUnique('reservations_voyage_seat_unique');
            $table->index('statut');
        });

        Schema::table('mode_reglements', function (Blueprint $table) {
            // true = paid online by card (CMI); false = paid at boarding / agency
            $table->boolean('en_ligne')->default(false)->after('mode_reglement');
        });
    }

    public function down(): void
    {
        Schema::table('mode_reglements', function (Blueprint $table) {
            $table->dropColumn('en_ligne');
        });

        // cancelled rows would break the old (voyage_id, num_siege) index
        DB::table('reservations')->where('statut', 'annulee')->delete();

        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['voyage_id', 'num_siege'], 'reservations_voyage_seat_unique');
            $table->dropUnique('reservations_voyage_active_seat_unique');
            $table->dropIndex(['statut']);
            $table->dropColumn(['statut', 'siege_actif', 'paye_le', 'paiement_ref', 'annulee_le', 'annulee_par', 'rembourse_le', 'rappel_envoye_le']);
        });
    }
};
