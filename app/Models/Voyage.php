<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Voyage extends Model
{
    use HasFactory;

    protected $guarded=['id'];

    // own "closer departure = higher price" rules; null = the default ones (App\Support\Tarif)
    protected $casts = ['majorations' => 'array'];

    /** Filter keys understood by scopeFilter() (admin list). */
    public const FILTERS = ['ville_depart_id', 'ville_arrivee_id', 'type_voyage_id', 'date_from', 'date_to', 'statut'];

    public function scopeFilter($query, array $filters)
    {
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        return $query
            ->when($filters['ville_depart_id'] ?? null, fn ($q, $id) => $q->where('ville_depart_id', $id))
            ->when($filters['ville_arrivee_id'] ?? null, fn ($q, $id) => $q->where('ville_arrivee_id', $id))
            ->when($filters['type_voyage_id'] ?? null, fn ($q, $id) => $q->where('type_voyage_id', $id))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('date_depart', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('date_depart', '<=', $date))
            ->when(($filters['statut'] ?? null) === 'a_venir', fn ($q) => $q->whereDate('date_depart', '>=', today()))
            ->when(($filters['statut'] ?? null) === 'passe', fn ($q) => $q->whereDate('date_depart', '<', today()));
    }

    /** Voyages still open for booking (departure not reached yet), soonest first. Used on the public site. */
    public function scopeBookable($query)
    {
        $now = now();
        $demain = $now->copy()->addDay()->toDateString();

        // plain comparisons, not whereDate(): MySQL can then use the (date_depart, heure_depart) index.
        // ">= tomorrow" also works when SQLite stores the date as "Y-m-d 00:00:00".
        return $query
            ->where(fn ($q) => $q->where('date_depart', '>=', $demain)
                ->orWhere(fn ($q) => $q->where('date_depart', '>=', $now->toDateString())->where('date_depart', '<', $demain)
                    ->where('heure_depart', '>', $now->format('H:i:s'))))
            ->orderBy('date_depart')
            ->orderBy('heure_depart');
    }

    /**
     * Public search: voyages that stop in $from and later in $to, where boarding in $from is still ahead
     * (a bus already gone from Fès can still be boarded in Imouzzer). $date filters on the boarding day.
     * Without cities it lists every voyage whose first stop is still ahead.
     */
    public function scopeServing($query, $from = null, $to = null, $date = null)
    {
        return $query->whereExists(function ($boarding) use ($from, $to, $date) {
            $boarding->from('voyage_arrets as a')
                ->whereColumn('a.voyage_id', 'voyages.id')
                ->where('a.passage_at', '>', now())
                ->when($from, fn ($q) => $q->where('a.ville_id', $from), fn ($q) => $q->where('a.ordre', 0))
                ->when($date, fn ($q) => $q->whereDate('a.passage_at', $date))
                ->whereExists(fn ($dropOff) => $dropOff->from('voyage_arrets as b')
                    ->whereColumn('b.voyage_id', 'a.voyage_id')
                    ->whereColumn('b.ordre', '>', 'a.ordre')
                    ->when($to, fn ($q) => $q->where('b.ville_id', $to)));
        })->orderBy('date_depart')->orderBy('heure_depart');
    }

    public function departAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(\Carbon\Carbon::parse($this->date_depart)->toDateString() . ' ' . $this->heure_depart);
    }

    public function arriveeAt(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(\Carbon\Carbon::parse($this->date_arrivee)->toDateString() . ' ' . $this->heure_arrivee);
    }

    // ================= Stops and segments =================

    /** The first and last stops always mirror the voyage's departure and arrival fields. */
    public function syncTerminalStops(): void
    {
        $arrets = $this->arrets()->get();
        $first = $arrets->first() ?? new VoyageArret(['voyage_id' => $this->id]);
        $first->fill(['ville_id' => $this->ville_depart_id, 'ordre' => 0, 'passage_at' => $this->departAt(), 'prix' => 0])->save();

        $last = $arrets->count() > 1 ? $arrets->last() : new VoyageArret(['voyage_id' => $this->id, 'ordre' => 1]);
        $last->fill(['ville_id' => $this->ville_arrivee_id, 'passage_at' => $this->arriveeAt(), 'prix' => $this->prix])->save();

        $this->unsetRelation('arrets');
    }

    /**
     * Replaces the intermediate stops. $stops = [['ville_id', 'heure' => 'H:i', 'prix' => price from the first stop], ...]
     * in route order; the day of each stop follows from the previous one (an hour earlier than the previous stop = next day).
     * Stops kept on the same city keep their id, so tickets sold on them stay attached.
     *
     * @throws \DomainException when a removed stop still has tickets
     */
    public function syncArrets(array $stops): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($stops) {
            $this->syncTerminalStops();
            $arrets = $this->arrets()->get();
            $first = $arrets->first();
            $last = $arrets->last();
            $middle = $arrets->slice(1, -1)->values();

            $kept = [];
            $previous = $first->passage_at->copy();
            foreach (array_values($stops) as $i => $stop) {
                $at = $previous->copy()->setTimeFromTimeString($stop['heure']);
                if ($at->lte($previous)) {
                    $at->addDay();
                }
                $row = $middle->first(fn ($a) => (int) $a->ville_id === (int) $stop['ville_id'] && ! in_array($a->id, $kept))
                    ?? new VoyageArret(['voyage_id' => $this->id]);
                $row->fill(['ville_id' => $stop['ville_id'], 'ordre' => $i + 1, 'passage_at' => $at, 'prix' => $stop['prix']])->save();
                $kept[] = $row->id;
                $previous = $at;
            }

            $removed = $middle->whereNotIn('id', $kept);
            if ($removed->isNotEmpty()) {
                $used = Reservation::whereIn('arret_depart_id', $removed->pluck('id'))->orWhereIn('arret_arrivee_id', $removed->pluck('id'))->exists();
                if ($used) {
                    throw new \DomainException(__('Un arrêt supprimé a déjà des billets vendus : annulez-les avant de retirer cet arrêt.'));
                }
                VoyageArret::whereIn('id', $removed->pluck('id'))->delete();
            }

            $last->update(['ordre' => count($stops) + 1]);
            $this->unsetRelation('arrets');
        });
    }

    /**
     * [boarding stop, drop-off stop] for a trip from $from to $to (city ids): the first stop in $from,
     * then the next stop in $to. Missing cities mean the first / last stop. Null when the bus doesn't do that trip.
     */
    public function segmentFor($from = null, $to = null): ?array
    {
        $arrets = $this->arrets;
        $a = $from ? $arrets->first(fn ($s) => (int) $s->ville_id === (int) $from) : $arrets->first();
        if (! $a) {
            return null;
        }
        $b = $to ? $arrets->first(fn ($s) => $s->ordre > $a->ordre && (int) $s->ville_id === (int) $to) : $arrets->last();

        return $b && $b->ordre > $a->ordre ? [$a, $b] : null;
    }

    /** Segment from stop ids (booking form), checked to belong to this voyage and to go forward. */
    public function segmentByIds($departId, $arriveeId): ?array
    {
        $a = $this->arrets->firstWhere('id', (int) $departId);
        $b = $this->arrets->firstWhere('id', (int) $arriveeId);

        return $a && $b && $b->ordre > $a->ordre ? [$a, $b] : null;
    }

    /**
     * Price of a segment TODAY: base price + the "closer departure" increase (App\Support\Tarif).
     * Every booking (website, counter, ticket change) goes through here.
     */
    public function segmentPrice(VoyageArret $a, VoyageArret $b, ?\Carbon\Carbon $at = null): float
    {
        $base = $this->segmentBasePrice($a, $b);

        return round($base + \App\Support\Tarif::majoration($base, $a->passage_at, $at, $this->majorations), 2);
    }

    /** Whole trip, today's price (lists, wishlist, company page). */
    public function prixActuel(): float
    {
        $arrets = $this->arrets;

        return $arrets->count() >= 2
            ? $this->segmentPrice($arrets->first(), $arrets->last())
            : round((float) $this->prix + \App\Support\Tarif::majoration((float) $this->prix, $this->departAt(), null, $this->majorations), 2);
    }

    /** Base price of a segment (prices are stored from the first stop). */
    public function segmentBasePrice(VoyageArret $a, VoyageArret $b): float
    {
        return round(max(0, $b->prix - $a->prix), 2);
    }

    /**
     * Seats already sold on part of the segment [a, b): a seat left at Imouzzer is free from Imouzzer on.
     * $exceptReservationId: a ticket being moved does not block its own seat.
     */
    public function seatsTaken(VoyageArret $a, VoyageArret $b, ?int $exceptReservationId = null): array
    {
        $ordre = $this->arrets->pluck('ordre', 'id');
        $lastOrdre = (int) $this->arrets->max('ordre');
        $reservations = $this->relationLoaded('reservations')
            ? $this->reservations
            : $this->reservations()->get(['id', 'num_siege', 'arret_depart_id', 'arret_arrivee_id']);

        return $reservations
            ->filter(fn ($r) => $r->id !== $exceptReservationId)
            ->filter(fn ($r) => ($ordre[$r->arret_depart_id] ?? 0) < $b->ordre && ($ordre[$r->arret_arrivee_id] ?? $lastOrdre) > $a->ordre)
            ->pluck('num_siege')->map(fn ($s) => (int) $s)->unique()->values()->all();
    }

    public function seatsLeft(VoyageArret $a, VoyageArret $b): int
    {
        return max(0, (int) ($this->autocar?->nbr_siege ?? 0) - count($this->seatsTaken($a, $b)));
    }

    public function isPast(): bool
    {
        return $this->date_depart < today()->toDateString();
    }

    /** True once the bus has left (date + hour), so booking must be refused. */
    public function hasDeparted(): bool
    {
        return \Carbon\Carbon::parse($this->date_depart . ' ' . $this->heure_depart)->isPast();
    }

    /** Booked seats in % of the bus capacity (needs reservations_count + autocar loaded). */
    public function occupancyRate(): int
    {
        $capacity = (int) ($this->autocar?->nbr_siege ?? 0);
        $booked = $this->reservations_count ?? $this->reservations()->count();

        return $capacity > 0 ? (int) min(100, round($booked * 100 / $capacity)) : 0;
    }

    public function villeDepart()
    {
        return $this->belongsTo(Ville::class, 'ville_depart_id');
    }

    public function villeArrivee()
    {
        return $this->belongsTo(Ville::class, 'ville_arrivee_id');
    }

    public function autocar()
    {
        return $this->belongsTo(Autocar::class);
    }

    public function typeVoyage()
    {
        return $this->belongsTo(TypeVoyage::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function arrets()
    {
        return $this->hasMany(VoyageArret::class)->orderBy('ordre');
    }

    public function chauffeur()
    {
        return $this->belongsTo(User::class, 'chauffeur_id');
    }

    public function estEnRetard(): bool
    {
        return (int) $this->retard_minutes > 0;
    }

    public function heureDepartEstimee(): string
    {
        return $this->departAt()->addMinutes((int) $this->retard_minutes)->format('H:i');
    }

    public function heureArriveeEstimee(): string
    {
        return $this->arriveeAt()->addMinutes((int) $this->retard_minutes)->format('H:i');
    }

    /**
     * Operational data for the chauffeur: stops, arrival times, passenger counts, and seat numbers.
     * Strictly protects client privacy: NO client names, NO phone numbers, NO emails, NO prices.
     */
    public function statsChauffeur(): array
    {
        $capacite = (int) ($this->autocar?->nbr_siege ?? 0);
        $arrets = $this->arrets()->with('ville')->orderBy('ordre')->get();
        $billets = $this->reservations()
            ->where('statut', '!=', Reservation::ANNULEE)
            ->where('statut', '!=', Reservation::EN_ATTENTE)
            ->with(['villeDepart', 'villeArrivee'])
            ->get();

        $totalClients = $billets->count();
        $aBord = $billets->filter->isBoarded()->count();

        // Calculate flow per stop
        $cumul = 0;
        $arretsStats = [];
        $retard = (int) $this->retard_minutes;

        foreach ($arrets as $arret) {
            $montees = $billets->where('arret_depart_id', $arret->id)->count();
            $descentes = $billets->where('arret_arrivee_id', $arret->id)->count();
            $cumul = max(0, $cumul + $montees - $descentes);

            $passagePrevu = $arret->passage_at ? \Carbon\Carbon::parse($arret->passage_at) : null;
            $passageEstime = $passagePrevu ? $passagePrevu->copy()->addMinutes($retard) : null;

            $arretsStats[] = [
                'ville' => $arret->ville?->ville ?? 'Arrêt',
                'ordre' => (int) $arret->ordre,
                'passage_prevu' => $passagePrevu?->format('H:i') ?? '—',
                'passage_estime' => $passageEstime?->format('H:i') ?? '—',
                'montees' => $montees,
                'descentes' => $descentes,
                'passagers_a_bord' => $cumul,
            ];
        }

        // Anonymized seat map (1 to $capacite)
        $siegesOccupes = $billets->keyBy('num_siege');
        $sieges = [];
        for ($i = 1; $i <= $capacite; $i++) {
            $billet = $siegesOccupes->get($i);
            $sieges[] = [
                'numero' => $i,
                'occupe' => (bool) $billet,
                'embarque' => (bool) $billet?->isBoarded(),
                'statut' => $billet ? ($billet->isBoarded() ? 'embarque' : 'reserve') : 'libre',
                'segment' => $billet ? ($billet->villeDepart?->ville . ' → ' . $billet->villeArrivee?->ville) : null,
            ];
        }

        return [
            'capacite' => $capacite,
            'total_clients' => $totalClients,
            'a_bord' => $aBord,
            'restant_a_embarquer' => max(0, $totalClients - $aBord),
            'taux_remplissage' => $capacite > 0 ? (int) min(100, round($totalClients * 100 / $capacite)) : 0,
            'arrets' => $arretsStats,
            'sieges' => $sieges,
        ];
    }

    protected static function booted(): void
    {
        // every voyage always has its departure and arrival stops, whatever created it (form, seeder, factory)
        static::saved(fn (self $voyage) => $voyage->syncTerminalStops());

        // company space (admin pages only, see App\Support\SocieteScope)
        static::addGlobalScope('societe', \App\Support\SocieteScope::viaAutocar());
    }
}
