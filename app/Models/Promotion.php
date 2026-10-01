<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Promo code typed on the payment page (Admin > Promotions): a % or an amount off the whole order. */
class Promotion extends Model
{
    protected $fillable = ['code', 'type', 'valeur', 'min_montant', 'debut', 'fin', 'max_utilisations', 'max_par_client', 'actif'];

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
     * $verrou: inside the booking transaction, the code row is locked so two orders cannot pass the limit together.
     */
    public static function appliquer(?string $code, float $total, ?User $user = null, bool $verrou = false): array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return [null, 0.0, null];
        }
        $promo = static::where('code', $code)->when($verrou, fn ($q) => $q->lockForUpdate())->first();

        $refus = match (true) {
            ! $promo || ! $promo->actif => __('Ce code promo n\'existe pas.'),
            $promo->debut && today()->lt($promo->debut) => __('Ce code promo n\'est valable qu\'à partir du :date.', ['date' => $promo->debut->format('d/m/Y')]),
            $promo->fin && today()->gt($promo->fin) => __('Ce code promo a expiré.'),
            $promo->max_utilisations !== null && $promo->commandes()->count() >= $promo->max_utilisations => __('Ce code promo a déjà été utilisé le nombre de fois prévu.'),
            $user && $promo->max_par_client !== null && $promo->commandes($user)->count() >= $promo->max_par_client => __('Vous avez déjà utilisé ce code promo.'),
            $promo->min_montant && $total < $promo->min_montant => __('Ce code promo demande une commande d\'au moins :montant DH.', ['montant' => number_format($promo->min_montant, 2, ',', ' ')]),
            default => null,
        };
        if ($refus) {
            return [null, 0.0, $refus];
        }

        return [$promo, $promo->remisePour($total), null];
    }

    /** Orders (not cancelled) that used this code, by everyone or by $user: a cancelled order gives its use back. */
    public function commandes(?User $user = null): \Illuminate\Support\Collection
    {
        return Reservation::withoutGlobalScope('societe')->where('promotion_id', $this->id)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('commande')->distinct()->pluck('commande');
    }

    /** Keeps the "used N times" counter of the admin list in line with the live orders. */
    public function recompter(): void
    {
        $this->forceFill(['utilisations' => $this->commandes()->count()])->save();
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
