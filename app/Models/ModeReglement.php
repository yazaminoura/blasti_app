<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeReglement extends Model
{
    protected $fillable = ['mode_reglement', 'en_ligne', 'en_agence'];

    /**
     * en_ligne = paid by card on the CMI page; en_agence = paid at an agency / payment point with the order code
     * before config('safar.agence_delai_heures'); otherwise paid at boarding.
     */
    protected $casts = ['en_ligne' => 'boolean', 'en_agence' => 'boolean'];
}
