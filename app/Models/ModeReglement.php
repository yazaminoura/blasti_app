<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeReglement extends Model
{
    protected $fillable = ['mode_reglement', 'en_ligne'];

    /** en_ligne = paid by card on the CMI page; otherwise paid at boarding / agency */
    protected $casts = ['en_ligne' => 'boolean'];
}
