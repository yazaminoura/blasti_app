<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One QR code scan at the bus door (see App\Support\Controle). */
class Scan extends Model
{
    protected $fillable = ['reservation_id', 'user_id', 'voyage_id', 'resultat'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class)->withoutGlobalScopes();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
