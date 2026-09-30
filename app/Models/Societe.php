<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Societe extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // company space: a company account only sees itself (admin pages only, see App\Support\SocieteScope)
        static::addGlobalScope('societe', \App\Support\SocieteScope::direct('id'));
    }


    public function autocars(){
        return $this->hasMany(Autocar::class);

    }
}
