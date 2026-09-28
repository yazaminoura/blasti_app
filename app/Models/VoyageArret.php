<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One stop of a voyage: the city, when the bus passes there and the price from the first stop. */
class VoyageArret extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'passage_at' => 'datetime',
        'prix' => 'float',
        'ordre' => 'integer',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }
}
