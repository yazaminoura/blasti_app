<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Review of a trip (1 to 5 stars + comment), one per ticket, shown as the company's rating. */
class Avis extends Model
{
    protected $table = 'avis';

    protected $fillable = ['reservation_id', 'user_id', 'societe_id', 'note', 'commentaire', 'publie'];

    protected $casts = ['publie' => 'boolean', 'note' => 'integer'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }

    /** [societe_id => ['moyenne' => 4.3, 'nombre' => 12]] for the published reviews. */
    public static function notesParSociete(): \Illuminate\Support\Collection
    {
        return static::where('publie', true)->whereNotNull('societe_id')
            ->selectRaw('societe_id, AVG(note) as moyenne, COUNT(*) as nombre')
            ->groupBy('societe_id')->get()
            ->mapWithKeys(fn ($r) => [$r->societe_id => ['moyenne' => round((float) $r->moyenne, 1), 'nombre' => (int) $r->nombre]]);
    }
}
