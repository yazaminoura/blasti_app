<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Promo code typed on the payment page (Admin > Promotions): a % or an amount off the whole order. */
class Promotion extends Model
{
    protected $fillable = ['code', 'type', 'valeur', 'min_montant', 'debut', 'fin', 'max_utilisations', 'actif'];

    protected $casts = ['debut' => 'date', 'fin' => 'date', 'actif' => 'boolean', 'valeur' => 'float', 'min_montant' => 'float'];

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * [Promotion, discount] for an order of $total DH, or [null, 0, reason] when the code cannot be used.
     */
    public static function appliquer(?string $code, float $total): array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return [null, 0.0, null];
        }
        $promo = static::where('code', $code)->first();

        $refus = match (true) {
            ! $promo || ! $promo->actif => __('Ce code promo n\'existe pas.'),
            $promo->debut && today()->lt($promo->debut) => __('Ce code promo n\'est valable qu\'à partir du :date.', ['date' => $promo->debut->format('d/m/Y')]),
            $promo->fin && today()->gt($promo->fin) => __('Ce code promo a expiré.'),
            $promo->max_utilisations !== null && $promo->utilisations >= $promo->max_utilisations => __('Ce code promo a déjà été utilisé le nombre de fois prévu.'),
            $promo->min_montant && $total < $promo->min_montant => __('Ce code promo demande une commande d\'au moins :montant DH.', ['montant' => number_format($promo->min_montant, 2, ',', ' ')]),
            default => null,
        };
        if ($refus) {
            return [null, 0.0, $refus];
        }

        return [$promo, $promo->remisePour($total), null];
    }

    /** Discount on an order of $total DH (never more than the order). */
    public function remisePour(float $total): float
    {
        $remise = $this->type === 'montant' ? $this->valeur : $total * $this->valeur / 100;

        return round(min($total, max(0, $remise)), 2);
    }

    /** "-10 %" / "-20,00 DH" */
    public function libelle(): string
    {
        return $this->type === 'montant'
            ? '-' . number_format($this->valeur, 2, ',', ' ') . ' DH'
            : '-' . rtrim(rtrim(number_format($this->valeur, 2, ',', ''), '0'), ',') . ' %';
    }
}
