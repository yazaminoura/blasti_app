<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One seat = one reservation per voyage, enforced by the database (safety net behind the
     * booking lock). Existing duplicates (old demo data) are first moved to a free seat of the same bus.
     */
    public function up(): void
    {
        $duplicates = DB::table('reservations')
            ->select('voyage_id', 'num_siege')
            ->groupBy('voyage_id', 'num_siege')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $capacity = (int) DB::table('voyages')
                ->join('autocars', 'autocars.id', '=', 'voyages.autocar_id')
                ->where('voyages.id', $dup->voyage_id)
                ->value('autocars.nbr_siege');

            // keep the oldest booking on the seat, move the others
            $extra = DB::table('reservations')
                ->where('voyage_id', $dup->voyage_id)->where('num_siege', $dup->num_siege)
                ->orderBy('id')->pluck('id')->slice(1);

            foreach ($extra as $id) {
                $taken = DB::table('reservations')->where('voyage_id', $dup->voyage_id)->pluck('num_siege')->all();
                $free = collect(range(1, max(1, $capacity)))->first(fn ($seat) => ! in_array($seat, $taken));
                if ($free === null) {
                    throw new RuntimeException("Voyage {$dup->voyage_id} : plus de siège libre pour la réservation {$id} en double. Corrigez-la à la main puis relancez la migration.");
                }
                DB::table('reservations')->where('id', $id)->update(['num_siege' => $free]);
            }
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['voyage_id', 'num_siege'], 'reservations_voyage_seat_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_voyage_seat_unique');
        });
    }
};
