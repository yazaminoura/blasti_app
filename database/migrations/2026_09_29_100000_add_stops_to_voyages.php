<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A voyage is now an ordered list of stops (Fès → Imouzzer → Ifrane → … → Rabat).
     * A reservation covers one segment (boarding stop → drop-off stop), so the same seat can be sold
     * Fès → Imouzzer and again Imouzzer → Rabat. Seat uniqueness is checked in code (overlapping
     * segments), so the (voyage_id, siege_actif) unique index goes away.
     *
     * Existing voyages get two stops (departure and arrival) and their reservations cover the whole trip.
     */
    public function up(): void
    {
        Schema::create('voyage_arrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->foreignId('ville_id')->constrained('villes');
            $table->unsignedSmallInteger('ordre');
            $table->dateTime('passage_at');
            // price from the first stop; a segment costs prix(drop-off) - prix(boarding)
            $table->decimal('prix', 10, 2)->default(0);
            $table->timestamps();
            $table->index(['voyage_id', 'ordre']);
            $table->index(['ville_id', 'passage_at']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('arret_depart_id')->nullable()->after('voyage_id')->constrained('voyage_arrets')->nullOnDelete();
            $table->foreignId('arret_arrivee_id')->nullable()->after('arret_depart_id')->constrained('voyage_arrets')->nullOnDelete();
            $table->decimal('montant_rembourse', 10, 2)->nullable()->after('rembourse_le');
            $table->decimal('frais_annulation', 10, 2)->nullable()->after('montant_rembourse');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_voyage_active_seat_unique');
            $table->index(['voyage_id', 'num_siege'], 'reservations_voyage_seat_index');
        });

        $now = now();
        DB::table('voyages')->orderBy('id')->chunkById(200, function ($voyages) use ($now) {
            foreach ($voyages as $voyage) {
                $depart = DB::table('voyage_arrets')->insertGetId([
                    'voyage_id' => $voyage->id, 'ville_id' => $voyage->ville_depart_id, 'ordre' => 0,
                    'passage_at' => substr($voyage->date_depart, 0, 10) . ' ' . $voyage->heure_depart,
                    'prix' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $arrivee = DB::table('voyage_arrets')->insertGetId([
                    'voyage_id' => $voyage->id, 'ville_id' => $voyage->ville_arrivee_id, 'ordre' => 1,
                    'passage_at' => substr($voyage->date_arrivee, 0, 10) . ' ' . $voyage->heure_arrivee,
                    'prix' => $voyage->prix, 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('reservations')->where('voyage_id', $voyage->id)
                    ->update(['arret_depart_id' => $depart, 'arret_arrivee_id' => $arrivee]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_voyage_seat_index');
            $table->unique(['voyage_id', 'siege_actif'], 'reservations_voyage_active_seat_unique');
            $table->dropConstrainedForeignId('arret_depart_id');
            $table->dropConstrainedForeignId('arret_arrivee_id');
            $table->dropColumn(['montant_rembourse', 'frais_annulation']);
        });

        Schema::dropIfExists('voyage_arrets');
    }
};
