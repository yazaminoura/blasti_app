<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Price by how far away the departure is. Rules: "J days or less before the departure day: + amount (DH) or + %".
 * The closest matching rule applies; further away than every rule = the base price of the voyage.
 * Example: 7 days → +10 DH, 4 days → +20 DH.
 *
 * Each voyage has its own rules (voyages.majorations), or null = the default rules
 * (Admin > Paramètres > Tarifs par défaut, config safar.majorations). An empty list = no increase.
 */
class Tarif
{
    public const TYPES = ['montant' => 'DH', 'pourcentage' => '%'];

    /**
     * @param  array|null  $propres  rules of a voyage (null = default rules)
     * @return array<int, array{jours: int, type: string, valeur: float}> sorted from the closest to the departure
     */
    public static function regles(?array $propres = null): array
    {
        return self::nettoyer($propres ?? config('safar.majorations', []));
    }

    /** Form lines -> clean rules (empty lines dropped, one rule per number of days, closest first). */
    public static function nettoyer(array $lignes): array
    {
        return collect($lignes)
            ->filter(fn ($r) => isset($r['jours'], $r['valeur']) && $r['jours'] !== '' && (float) $r['valeur'] > 0)
            ->map(fn ($r) => ['jours' => (int) $r['jours'], 'type' => ($r['type'] ?? 'montant') === 'pourcentage' ? 'pourcentage' : 'montant', 'valeur' => round((float) $r['valeur'], 2)])
            ->keyBy('jours')->sortBy('jours')->values()->all();
    }

    /** Calendar days from $at to the departure day (0 = today). */
    public static function joursAvant(Carbon $depart, ?Carbon $at = null): int
    {
        return (int) ($at ?? now())->copy()->startOfDay()->diffInDays($depart->copy()->startOfDay(), false);
    }

    /** The rule applying to a departure, or null (base price). */
    public static function regle(Carbon $depart, ?Carbon $at = null, ?array $propres = null): ?array
    {
        $jours = self::joursAvant($depart, $at);

        return collect(self::regles($propres))->first(fn ($r) => $jours <= $r['jours']);
    }

    /** Extra amount (DH) on a base price for a departure. */
    public static function majoration(float $base, Carbon $depart, ?Carbon $at = null, ?array $propres = null): float
    {
        $r = self::regle($depart, $at, $propres);
        if (! $r || $base <= 0) {
            return 0.0;
        }

        return round($r['type'] === 'pourcentage' ? $base * $r['valeur'] / 100 : $r['valeur'], 2);
    }

    /** "7 j : +10 DH · 4 j : +20 DH" (or "aucune augmentation"). */
    public static function resume(?array $propres = null): string
    {
        $regles = self::regles($propres);
        if (! $regles) {
            return 'aucune augmentation';
        }

        return collect($regles)->sortByDesc('jours')
            ->map(fn ($r) => $r['jours'] . ' j : +' . rtrim(rtrim(number_format($r['valeur'], 2, ',', ''), '0'), ',') . ' ' . self::TYPES[$r['type']])
            ->join(' · ');
    }
}
