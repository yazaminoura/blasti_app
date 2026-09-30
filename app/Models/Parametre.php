<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Site settings (a single row): name + color (Admin > Paramètres > Apparence),
 * contact details + social links (Admin > Paramètres > Coordonnées).
 * The values are copied into config('safar.*') at boot: views only read the config.
 */
class Parametre extends Model
{
    protected $table = 'parametres';

    protected $fillable = ['nom', 'couleur', 'telephone', 'email', 'adresse', 'facebook', 'instagram', 'tiktok', 'x', 'linkedin', 'majorations'];

    protected $casts = ['majorations' => 'array'];

    /** Column => config key (empty columns keep the .env / config default). */
    public const CONFIG = [
        'nom' => 'safar.nom', 'couleur' => 'safar.couleur',
        'telephone' => 'safar.contact.telephone', 'email' => 'safar.contact.email', 'adresse' => 'safar.contact.adresse',
        'facebook' => 'safar.social.facebook', 'instagram' => 'safar.social.instagram', 'tiktok' => 'safar.social.tiktok',
        'x' => 'safar.social.x', 'linkedin' => 'safar.social.linkedin',
    ];

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

        foreach (self::CONFIG as $champ => $cle) {
            if (filled($ligne->$champ ?? null)) {
                config([$cle => $ligne->$champ]);
            }
        }
        // price by how far away the departure is (App\Support\Tarif)
        if (is_array($ligne->majorations ?? null)) {
            config(['safar.majorations' => $ligne->majorations]);
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
