<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autocar extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // company space (admin pages only, see App\Support\SocieteScope)
        static::addGlobalScope('societe', \App\Support\SocieteScope::direct());
    }

    public function societe(){
        return $this->belongsTo(Societe::class);
    }

    public function voyages(){
        return $this->hasMany(Voyage::class);
    }

    public function equipements()
    {
        return $this->belongsToMany(Equipement::class, 'autocar_equipements', 'autocar_id', 'equipement_id');
    }

    public function options()
    {
        return $this->belongsToMany(Option::class, 'autocar_options', 'autocar_id', 'option_id');
    }
}
