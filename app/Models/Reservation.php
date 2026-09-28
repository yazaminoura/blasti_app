<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public const EN_ATTENTE = 'en_attente'; // card payment in progress (seat held)
    public const CONFIRMEE = 'confirmee';
    public const ANNULEE = 'annulee';

    /** A card payment not finished after this many minutes releases the seat. */
    public const DELAI_PAIEMENT_MINUTES = 20;

    protected $casts = [
        'paye_le' => 'datetime',
        'annulee_le' => 'datetime',
        'rembourse_le' => 'datetime',
        'rappel_envoye_le' => 'datetime',
    ];

    /**
     * Cancelled reservations stay in the database (history, refunds) but are hidden everywhere by default:
     * seat map, occupancy, revenue, dashboards, exports. Use withoutGlobalScope('active') to see them.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('active', fn ($query) => $query->where((new static)->qualifyColumn('statut'), '!=', self::ANNULEE));

        static::creating(function (self $reservation) {
            // the seat really held by the reservation (emptied on cancellation)
            if ($reservation->statut !== self::ANNULEE && $reservation->siege_actif === null) {
                $reservation->siege_actif = $reservation->num_siege;
            }
            // no segment given: the whole trip, first stop to last stop
            if ($reservation->voyage_id && (! $reservation->arret_depart_id || ! $reservation->arret_arrivee_id)) {
                $arrets = VoyageArret::where('voyage_id', $reservation->voyage_id)->orderBy('ordre')->get(['id']);
                $reservation->arret_depart_id ??= $arrets->first()?->id;
                $reservation->arret_arrivee_id ??= $arrets->last()?->id;
            }
        });
    }

    /** Admin and ticket URLs must still open a cancelled reservation. */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('active')->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }

    //=========== State ===========
    public function isCancelled(): bool
    {
        return $this->statut === self::ANNULEE;
    }

    public function isPaid(): bool
    {
        return $this->paye_le !== null;
    }

    public function departAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(\Carbon\Carbon::parse($this->date_depart)->toDateString() . ' ' . $this->heure_depart);
    }

    /** A client may cancel until the boarding time, never after (the admin can cancel anytime). */
    public function canBeCancelledByClient(): bool
    {
        return ! $this->isCancelled() && $this->departAt()->isFuture();
    }

    /** What the ticket cost (price + fees). */
    public function total(): float
    {
        return round((float) $this->prix + (float) $this->frais, 2);
    }

    /**
     * Refund if the ticket were cancelled now: ['pourcentage', 'montant', 'frais'].
     * Unpaid ticket: nothing to refund. Company cancellation: 100 %. Client: config safar.annulation.paliers.
     */
    public function refundQuote(string $by = 'client', ?\Carbon\Carbon $at = null): array
    {
        if (! $this->isPaid()) {
            return ['pourcentage' => 0, 'montant' => 0.0, 'frais' => 0.0];
        }

        $percent = $by === 'client' ? self::refundPercent(($at ?? now())->diffInMinutes($this->departAt(), false) / 60) : 100;
        $amount = round($this->total() * $percent / 100, 2);

        return ['pourcentage' => $percent, 'montant' => $amount, 'frais' => round($this->total() - $amount, 2)];
    }

    /** % refunded for a cancellation $hours before boarding (0 once the bus has left). */
    public static function refundPercent(float $hours): int
    {
        if ($hours <= 0) {
            return 0;
        }
        $steps = config('safar.annulation.paliers', [0 => 100]);
        krsort($steps);
        foreach ($steps as $minHours => $percent) {
            if ($hours >= $minHours) {
                return (int) $percent;
            }
        }

        return 0;
    }

    /** Text of the "cancel this ticket?" confirmation, with the refund the client would get now. */
    public function cancelConfirmText(): string
    {
        if (! $this->isPaid()) {
            return __('Annuler ce billet ? Le siège sera libéré. Le billet n\'est pas encore payé : l\'annulation est gratuite.');
        }
        $quote = $this->refundQuote();

        return $quote['montant'] > 0
            ? __('Annuler ce billet ? Vous serez remboursé de :montant DH sur :total DH (:pct %).', [
                'montant' => number_format($quote['montant'], 2, ',', ' '), 'total' => number_format($this->total(), 2, ',', ' '), 'pct' => $quote['pourcentage'],
            ])
            : __('Annuler ce billet ? À ce délai du départ, aucun remboursement n\'est prévu.');
    }

    public function needsRefund(): bool
    {
        // old cancellations have no amount stored: the full price is due
        return $this->isCancelled() && $this->isPaid() && $this->rembourse_le === null
            && ($this->montant_rembourse === null || (float) $this->montant_rembourse > 0);
    }

    /** Amount to give back for a cancelled paid ticket. */
    public function amountToRefund(): float
    {
        return $this->montant_rembourse !== null ? (float) $this->montant_rembourse : $this->total();
    }

    /** Frees the seat, fixes the refund owed (see refundQuote) and keeps the reservation in the history. */
    public function cancel(string $by): void
    {
        $quote = $this->refundQuote($by);

        $this->forceFill([
            'statut' => self::ANNULEE,
            'siege_actif' => null,
            'annulee_le' => now(),
            'annulee_par' => $by,
            'montant_rembourse' => $this->isPaid() ? $quote['montant'] : null,
            'frais_annulation' => $this->isPaid() ? $quote['frais'] : null,
        ])->save();
    }

    public function markPaid(?string $reference = null): void
    {
        $this->forceFill([
            'statut' => self::CONFIRMEE,
            'paye_le' => $this->paye_le ?? now(),
            'paiement_ref' => $reference ?? $this->paiement_ref,
        ])->save();
    }

    /** Releases the seats of card payments abandoned for too long. */
    public static function expirePendingPayments(): int
    {
        $expired = static::where('statut', self::EN_ATTENTE)
            ->where('created_at', '<', now()->subMinutes(self::DELAI_PAIEMENT_MINUTES))
            ->get();
        $expired->each->cancel('systeme');

        return $expired->count();
    }

    /** [label, tone] for the admin/client badges. */
    public function statusBadge(): array
    {
        return match (true) {
            $this->isCancelled() && $this->needsRefund() => [__('Annulée · à rembourser'), 'danger'],
            $this->isCancelled() && $this->rembourse_le !== null => [__('Annulée · remboursée'), 'muted'],
            $this->isCancelled() => [__('Annulée'), 'muted'],
            $this->statut === self::EN_ATTENTE => [__('Paiement en cours'), 'warning'],
            $this->isPaid() => [__('Payée'), 'success'],
            default => [__('À payer à l\'embarquement'), 'info'],
        };
    }

    /** Filter keys understood by scopeFilter() (admin list + CSV export). */
    public const FILTERS = ['q', 'ville_depart_id', 'ville_arrivee_id', 'mode_reglement_id', 'date_from', 'date_to', 'statut'];

    //=========== Scopes ===========
    public function scopeFilter($query, array $filters)
    {
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        return $query
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('telephone', 'like', "%{$term}%")))
            ->when($filters['ville_depart_id'] ?? null, fn ($q, $id) => $q->where('ville_depart_id', $id))
            ->when($filters['ville_arrivee_id'] ?? null, fn ($q, $id) => $q->where('ville_arrivee_id', $id))
            ->when($filters['mode_reglement_id'] ?? null, fn ($q, $id) => $q->where('mode_reglement_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('date_depart', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('date_depart', '<=', $date))
            ->when(($filters['statut'] ?? null) === 'a_venir', fn ($q) => $q->whereDate('date_depart', '>=', today()))
            ->when(($filters['statut'] ?? null) === 'passe', fn ($q) => $q->whereDate('date_depart', '<', today()))
            // payment / cancellation states (cancelled ones need the "active" scope removed by the caller)
            ->when(($filters['statut'] ?? null) === 'a_payer', fn ($q) => $q->whereNull('paye_le')->where('statut', self::CONFIRMEE))
            ->when(($filters['statut'] ?? null) === 'payee', fn ($q) => $q->whereNotNull('paye_le'))
            ->when(($filters['statut'] ?? null) === 'annulee', fn ($q) => $q->where('statut', self::ANNULEE))
            ->when(($filters['statut'] ?? null) === 'a_rembourser', fn ($q) => $q->where('statut', self::ANNULEE)->whereNotNull('paye_le')->whereNull('rembourse_le')
                ->where(fn ($q) => $q->whereNull('montant_rembourse')->orWhere('montant_rembourse', '>', 0)));
    }

    //=========== Relations ===========
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function villeArrivee()
    {
        return $this->belongsTo(Ville::class);
    }

    public function villeDepart()
    {
        return $this->belongsTo(Ville::class);
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function arretDepart()
    {
        return $this->belongsTo(VoyageArret::class, 'arret_depart_id');
    }

    public function arretArrivee()
    {
        return $this->belongsTo(VoyageArret::class, 'arret_arrivee_id');
    }

    public function modeReglement()
    {
        return $this->belongsTo(ModeReglement::class);
    }

    public function autocar()
    {
        return $this->belongsTo(Autocar::class);
    }

    public function typeVoyage()
    {
        return $this->belongsTo(TypeVoyage::class);
    }
}
