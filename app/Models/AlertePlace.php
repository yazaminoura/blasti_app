<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * "Warn me when a seat frees up" on a full bus. When a ticket of that bus is cancelled,
 * everyone waiting gets an e-mail (first come, first served) — see Reservation::cancel().
 */
class AlertePlace extends Model
{
    protected $table = 'alertes_places';

    protected $fillable = ['voyage_id', 'user_id', 'email', 'arret_depart_id', 'arret_arrivee_id', 'envoyee_le'];

    protected $casts = ['envoyee_le' => 'datetime'];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    /** A seat of this bus was freed: e-mail the people waiting (once each), only while the bus hasn't left. */
    public static function notifier(int $voyageId): int
    {
        $voyage = Voyage::with(['arrets.ville', 'autocar'])->find($voyageId);
        if (! $voyage || $voyage->departAt()->isPast()) {
            return 0;
        }

        $sent = 0;
        foreach (static::where('voyage_id', $voyageId)->whereNull('envoyee_le')->get() as $alerte) {
            $segment = $alerte->arret_depart_id ? $voyage->segmentByIds($alerte->arret_depart_id, $alerte->arret_arrivee_id) : $voyage->segmentFor();
            if (! $segment || $voyage->seatsLeft(...$segment) < 1) {
                continue; // the freed seat is not on this person's part of the trip
            }
            try {
                \Illuminate\Support\Facades\Mail::to($alerte->email)->send(new \App\Mail\PlaceLibreMail($voyage, $segment[0], $segment[1]));
                $alerte->forceFill(['envoyee_le' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }
}
