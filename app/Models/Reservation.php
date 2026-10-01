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
        'modifiee_le' => 'datetime',
        'embarque_le' => 'datetime',
        'scanne_le' => 'datetime',
        'confirmation_demandee_le' => 'datetime',
        'presence_confirmee_le' => 'datetime',
    ];

    /**
     * Cancelled reservations stay in the database (history, refunds) but are hidden everywhere by default:
     * seat map, occupancy, revenue, dashboards, exports. Use withoutGlobalScope('active') to see them.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('active', fn ($query) => $query->where((new static)->qualifyColumn('statut'), '!=', self::ANNULEE));
        // company space (admin pages only, see App\Support\SocieteScope)
        static::addGlobalScope('societe', \App\Support\SocieteScope::viaAutocar());

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

    public function arriveeAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(\Carbon\Carbon::parse($this->date_arrivee)->toDateString() . ' ' . $this->heure_arrivee);
    }

    /** The trip is over and the client has not rated it yet (one review per ticket). */
    public function peutEtreNote(): bool
    {
        return ! $this->isCancelled() && $this->arriveeAt()->isPast() && ! $this->avis()->exists();
    }

    /**
     * A client may cancel until the boarding time, never after, and never once the controller let them in
     * (boarding opens hours before departure). The admin can cancel anytime.
     */
    public function canBeCancelledByClient(): bool
    {
        return ! $this->isCancelled() && ! $this->isBoarded() && $this->departAt()->isFuture();
    }

    /**
     * The client may move the ticket to another departure of the same trip while boarding is at least
     * config('safar.modification_heures') hours away (not during a card payment in progress).
     */
    public function canBeChangedByClient(): bool
    {
        return ! $this->isCancelled()
            && $this->statut !== self::EN_ATTENTE
            && now()->diffInMinutes($this->departAt(), false) >= (int) config('safar.modification_heures') * 60;
    }

    /** Last moment the ticket can still be moved to another departure. */
    public function changeDeadline(): \Carbon\Carbon
    {
        return $this->departAt()->subHours((int) config('safar.modification_heures'));
    }

    /** Splits a discount between $n seats in cents: [3.34, 3.33, 3.33]. */
    public static function repartir(float $remise, int $n): array
    {
        if ($n < 1) {
            return [];
        }
        $cents = (int) round($remise * 100);
        $base = intdiv($cents, $n);

        return array_map(fn ($i) => ($base + ($i < $cents % $n ? 1 : 0)) / 100, range(0, $n - 1));
    }

    /**
     * Return-trip discount: $commande is a confirmed outbound order of this client, not cancelled, making the
     * opposite trip (B→A after A→B), leaving before $depart and not already used for another return.
     * It covers at most as many seats as the outbound order has. Returns [order code|null, discount].
     */
    public static function remiseRetour(?string $commande, ?User $user, VoyageArret $depart, VoyageArret $arrivee, float $prix, int $seats): array
    {
        $pourcent = (float) config('safar.remise_retour_pourcent');
        if (! $commande || ! $user || $pourcent <= 0) {
            return [null, 0.0];
        }
        $aller = static::where('commande', $commande)->where('user_id', $user->id)->where('statut', self::CONFIRMEE)->get();
        $first = $aller->first();
        $valide = $first
            && (int) $first->ville_arrivee_id === (int) $depart->ville_id
            && (int) $first->ville_depart_id === (int) $arrivee->ville_id
            && $first->departAt()->lt($depart->passage_at)
            && ! static::where('retour_de', $commande)->exists();

        return $valide ? [$commande, round($prix * min($seats, $aller->count()) * $pourcent / 100, 2)] : [null, 0.0];
    }

    /**
     * After an outbound ticket is cancelled, its return tickets keep the discount only for the outbound seats
     * still active: unpaid ones lose it (cancelling the outbound for free must not keep a cheaper return).
     */
    private function retirerRemiseRetour(): void
    {
        if (! $this->commande) {
            return;
        }
        $retours = static::where('retour_de', $this->commande)->whereNull('promotion_id')->where('remise', '>', 0)->orderBy('num_siege')->get();
        if ($retours->isEmpty()) {
            return;
        }
        $restants = static::where('commande', $this->commande)->count();
        $retours->slice($restants)->reject->isPaid()->each(fn ($r) => $r->forceFill(['remise' => 0])->save());
    }

    /** Traveller of this seat (typed at booking), else the account holder. */
    public function passager(): string
    {
        return $this->passager_nom ?: (string) $this->user?->name;
    }

    /** The controller scanned the ticket and let the traveller in. */
    public function isBoarded(): bool
    {
        return $this->embarque_le !== null;
    }

    /** Unpaid ticket still waiting for the client's "I'm coming" (see config safar.confirmation). */
    public function awaitsPresence(): bool
    {
        return config('safar.confirmation.active')
            && $this->statut === self::CONFIRMEE
            && ! $this->isPaid()
            && $this->presence_confirmee_le === null;
    }

    /** Link of the "I'm coming" button in the e-mail (signed: works without logging in). */
    public function presenceUrl(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('ticket.presence', $this);
    }

    /** When the "pay or confirm" e-mail is (or was) sent: `demande_heures` after booking. */
    public function presenceAskedAt(): \Carbon\Carbon
    {
        return ($this->confirmation_demandee_le ?? $this->created_at->copy()->addHours((int) config('safar.confirmation.demande_heures')))->copy();
    }

    /** Last moment to pay or answer the e-mail before the unpaid ticket is cancelled. */
    public function presenceDeadline(): \Carbon\Carbon
    {
        return $this->presenceAskedAt()->addHours((int) config('safar.confirmation.limite_heures'));
    }

    /** Client confirmed but has not paid: last moment to pay (online, agency or counter). */
    public function paymentDeadline(): \Carbon\Carbon
    {
        return $this->presenceAskedAt()->addHours((int) config('safar.confirmation.paiement_heures'));
    }

    /** When this unpaid ticket will be cancelled, or null if never (the bus leaves first: paid at the door). */
    public function cancellationDue(): ?\Carbon\Carbon
    {
        $due = $this->presence_confirmee_le ? $this->paymentDeadline() : $this->presenceDeadline();

        return $due->lt($this->departAt()) ? $due : null;
    }

    /** What the client really paid (tickets paid before this was stored: their price). */
    public function montantPaye(): float
    {
        if (! $this->isPaid()) {
            return 0.0;
        }

        return $this->montant_paye !== null ? (float) $this->montant_paye : $this->total();
    }

    /**
     * Money still due at boarding: the whole price if unpaid, the supplement if a paid ticket was moved
     * to a dearer departure. A cheaper departure is not refunded (the client is told before changing).
     */
    public function resteAPayer(): float
    {
        if ($this->isCancelled()) {
            return 0.0;
        }

        return round(max(0, $this->total() - $this->montantPaye()), 2);
    }

    /**
     * The tickets booked together with this one (same "commande"), this one included, by seat.
     * Old reservations have no order: they are alone.
     */
    public function commandeBillets(): \Illuminate\Support\Collection
    {
        if (! $this->commande) {
            return collect([$this]);
        }

        return static::withoutGlobalScope('active')->where('commande', $this->commande)->orderBy('num_siege')->get();
    }

    /**
     * The tickets of a query, one group per order (scheduled e-mails: one per order, not per seat). The rows
     * are read once before any change: paging while marking them sent would skip some.
     */
    public static function parCommande($query): \Illuminate\Support\Collection
    {
        return $query->with('user')->orderBy('id')->get()->groupBy(fn (self $r) => $r->commande ?: 'id-' . $r->id);
    }

    /** New order reference, e.g. "C7K2M9QXA". */
    public static function nouvelleCommande(): string
    {
        do {
            $code = 'C' . strtoupper(\Illuminate\Support\Str::random(9));
        } while (static::withoutGlobalScope('active')->where('commande', $code)->exists());

        return $code;
    }

    /** Link opened by the QR code of the ticket (signed: it cannot be guessed from the ticket number). */
    public function verificationUrl(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('ticket.verify', $this);
    }

    /** QR code of the ticket as an SVG data URI (works in the PDF and on the ticket page). */
    public function qrCodeDataUri(int $size = 150): string
    {
        $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size($size)->margin(0)->errorCorrection('M')
            ->generate($this->verificationUrl());

        return 'data:image/svg+xml;base64,' . base64_encode((string) $svg);
    }

    /** What the ticket cost (price + fees). */
    public function total(): float
    {
        // remise: promo code or return-trip discount
        return round(max(0, (float) $this->prix + (float) $this->frais - (float) $this->remise), 2);
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

        $at ??= now();
        $percent = match (true) {
            $by !== 'client' => 100,                  // cancelled by the company / the team: everything back
            $this->departAt()->lte($at) => 0,         // the bus has left
            default => self::refundPercent((int) $at->copy()->startOfDay()->diffInDays($this->departAt()->copy()->startOfDay())),
        };
        // refunds are based on what was really paid (a supplement still due is not refunded)
        $amount = round($this->montantPaye() * $percent / 100, 2);

        return ['pourcentage' => $percent, 'montant' => $amount, 'frais' => round($this->montantPaye() - $amount, 2)];
    }

    /** % refunded for a cancellation $days calendar days before the departure day (0 = on the departure day). */
    public static function refundPercent(int $days): int
    {
        if ($days < 0) {
            return 0;
        }
        $steps = config('safar.annulation.paliers', [0 => 100]);
        krsort($steps);
        foreach ($steps as $minDays => $percent) {
            if ($days >= $minDays) {
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
        return $this->montant_rembourse !== null ? (float) $this->montant_rembourse : $this->montantPaye();
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

        $this->retirerRemiseRetour();
        // a cancelled order gives its promo code use back
        $this->promotion?->recompter();

        // a seat is free again: tell the people waiting for this bus ("Prévenez-moi")
        // (after the response, once per bus even when several seats are cancelled together)
        if ($voyageId = (int) $this->voyage_id) {
            \App\Support\ApresReponse::executer(fn () => AlertePlace::notifier($voyageId), 'alerte-places-' . $voyageId);
        }
    }

    public function markPaid(?string $reference = null): void
    {
        $this->forceFill([
            'statut' => self::CONFIRMEE,
            'paye_le' => $this->paye_le ?? now(),
            // everything due is now paid (first payment, or the supplement after a change of departure)
            'montant_paye' => $this->total(),
            'paiement_ref' => $reference ?? $this->paiement_ref,
        ])->save();
    }

    /**
     * Releases the seats of card payments abandoned for too long. Each row is re-read under a lock: the CMI
     * callback may be confirming the same ticket at this very moment (see PaymentController::callback).
     */
    public static function expirePendingPayments(): int
    {
        $ids = static::where('statut', self::EN_ATTENTE)
            ->where('created_at', '<', now()->subMinutes(self::DELAI_PAIEMENT_MINUTES))
            ->pluck('id');

        return $ids->filter(fn ($id) => \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $billet = static::lockForUpdate()->find($id);
            if (! $billet || $billet->statut !== self::EN_ATTENTE || $billet->isPaid()) {
                return false;
            }
            $billet->cancel('systeme');

            return true;
        }))->count();
    }

    /**
     * A card payment confirmed after the seat hold expired: the ticket comes back if its seat is still free
     * on its segment (call inside a transaction holding the voyage lock). False = seat resold, refund it.
     */
    public function revivre(?Voyage $voyage): bool
    {
        $depart = $voyage?->arrets->firstWhere('id', $this->arret_depart_id);
        $arrivee = $voyage?->arrets->firstWhere('id', $this->arret_arrivee_id);
        if (! $depart || ! $arrivee || in_array((int) $this->num_siege, $voyage->seatsTaken($depart, $arrivee, $this->id))) {
            return false;
        }
        $this->forceFill(['statut' => self::EN_ATTENTE, 'siege_actif' => $this->num_siege, 'annulee_le' => null, 'annulee_par' => null,
            'montant_rembourse' => null, 'frais_annulation' => null])->save();

        return true;
    }

    /** [label, tone] for the admin/client badges. */
    public function statusBadge(): array
    {
        return match (true) {
            $this->isCancelled() && $this->needsRefund() => [__('Annulée · à rembourser'), 'danger'],
            $this->isCancelled() && $this->rembourse_le !== null => [__('Annulée · remboursée'), 'muted'],
            $this->isCancelled() => [__('Annulée'), 'muted'],
            $this->statut === self::EN_ATTENTE => [__('Paiement en cours'), 'warning'],
            $this->isPaid() && $this->resteAPayer() > 0 => [__('Payée · supplément :montant DH à régler', ['montant' => number_format($this->resteAPayer(), 2, ',', ' ')]), 'warning'],
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
            // client (name, e-mail, phone), traveller name, or order code (agency payment: the client gives his code)
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('telephone', 'like', "%{$term}%"))
                ->orWhere('passager_nom', 'like', "%{$term}%")
                ->orWhere('commande', strtoupper(trim($term)))))
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

    /** Staff member who let the traveller in. */
    /** Staff member who sold the ticket at the counter (Guichet); null = booked online. */
    public function venduPar()
    {
        return $this->belongsTo(User::class, 'vendu_par');
    }

    public function embarquePar()
    {
        return $this->belongsTo(User::class, 'embarque_par');
    }

    /** Money collected by staff for this ticket (cash drawer). */
    public function encaissements()
    {
        return $this->hasMany(Encaissement::class)->latest();
    }

    public function scans()
    {
        return $this->hasMany(Scan::class)->latest();
    }

    /**
     * A staff member collects what is still due (whole price, or the supplement after a change of departure).
     * Returns the amount collected, 0 if nothing was due.
     */
    public function encaisser(User $par, string $mode = 'especes', bool $recu = true): float
    {
        // the row is re-read under a lock: a double click / double scan must not collect the same money twice
        $montant = \Illuminate\Support\Facades\DB::transaction(function () use ($par, $mode) {
            $frais = static::withoutGlobalScopes()->lockForUpdate()->find($this->id);
            $this->setRawAttributes($frais->getAttributes(), true);
            $montant = $this->resteAPayer();
            if ($montant <= 0) {
                return 0.0;
            }
            $this->encaissements()->create(['user_id' => $par->id, 'montant' => $montant, 'mode' => $mode]);
            $this->markPaid($mode === 'carte' ? 'guichet-carte' : 'guichet');

            return $montant;
        });
        if ($montant <= 0) {
            return 0.0;
        }
        // receipt to the traveller at once: a payment the staff did not record = no receipt = the client complains
        if ($recu) {
            \App\Mail\ReservationMail::sendTo($this, 'paiement');
        }

        return $montant;
    }

    /** The controller lets the traveller in (first time only: a second scan never changes the time). */
    public function embarquer(User $par): void
    {
        if ($this->embarque_le) {
            return;
        }
        $this->forceFill(['embarque_le' => now(), 'embarque_par' => $par->id, 'scanne_le' => $this->scanne_le ?? now()])->save();
    }

    public function arretDepart()
    {
        return $this->belongsTo(VoyageArret::class, 'arret_depart_id');
    }

    public function arretArrivee()
    {
        return $this->belongsTo(VoyageArret::class, 'arret_arrivee_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function avis()
    {
        return $this->hasOne(Avis::class);
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
