<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Money collected for a ticket by a staff member (cash or card terminal). */
class Encaissement extends Model
{
    public const MODES = ['especes' => 'Espèces', 'carte' => 'Carte (TPE)'];

    protected $fillable = ['reservation_id', 'user_id', 'montant', 'mode'];

    protected $casts = ['montant' => 'decimal:2'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class)->withoutGlobalScopes();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
