<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Brand settings (a single row), edited in Admin > Paramètres > Apparence.
 * The values are copied into config('safar.*') at boot: views only read the config.
 */
class Parametre extends Model
{
    protected $table = 'parametres';

    protected $fillable = ['nom', 'couleur'];

    public static function actuel(): self
    {
        return static::query()->first() ?? new static();
    }

    public static function appliquerALaConfig(): void
    {
        try {
            if (! Schema::hasTable('parametres')) {
                return;
            }
            $ligne = static::query()->first();
        } catch (\Throwable $e) {
            return; // database unavailable (install, tests): keep the defaults
        }
        if (! $ligne) {
            return;
        }

        foreach (['nom', 'couleur'] as $champ) {
            if (filled($ligne->$champ)) {
                config(["safar.$champ" => $ligne->$champ]);
            }
        }
    }

    /** "#0F766E" => "15, 118, 110" (for Bootstrap's --bs-*-rgb variables). */
    public static function rgb(string $hex, float $melangeBlanc = 0): string
    {
        $hex = ltrim($hex, '#');
        $canaux = array_map('hexdec', str_split(strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex, 2));

        return implode(', ', array_map(fn ($c) => (int) round($c + (255 - $c) * $melangeBlanc), $canaux));
    }
}
