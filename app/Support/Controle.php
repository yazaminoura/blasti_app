<?php

namespace App\Support;

use App\Models\Encaissement;
use App\Models\Reservation;
use App\Models\Scan;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/**
 * Ticket check at the bus door: reads what the scanner saw (the QR code link, or a ticket number typed by
 * hand), decides if the traveller may board, and keeps a trace of every scan (table scans).
 *
 * Verdict tone: "ok" = board, "payer" = collect the money first, "stop" = refuse.
 */
class Controle
{
    /** Boarding window around the ticket's departure time (hours before / after). */
    public const AVANT_HEURES = 6;
    public const APRES_HEURES = 3;

    /** Scan results as shown in the back office (scans.resultat). */
    public const LIBELLES = [
        'valable' => 'Valable', 'a_payer' => 'À encaisser', 'annule' => 'Annulé', 'non_confirme' => 'Paiement non confirmé',
        'deja_monte' => 'Déjà monté (2e présentation)', 'mauvais_bus' => 'Mauvais bus', 'parti' => 'Départ passé',
        'trop_tot' => 'Pas pour ce départ', 'introuvable' => 'Billet introuvable', 'illisible' => 'QR code non reconnu',
    ];

    /** Ticket id from a scanned QR code (only our own signed links) or a typed number; null if unreadable. */
    public static function numeroDuCode(string $code): ?int
    {
        $code = trim($code);
        if (ctype_digit($code)) {
            return (int) $code;
        }

        // the QR code is the signed link of the ticket check page (Reservation::verificationUrl)
        if (! str_starts_with($code, url('/') . '/billet/')) {
            return null;
        }
        $request = Request::create($code);
        if (! URL::hasValidSignature($request)) {
            return null;
        }
        try {
            $route = Route::getRoutes()->match($request);
        } catch (\Throwable $e) {
            return null;
        }

        return $route->getName() === 'ticket.verify' ? (int) $route->parameter('reservation') : null;
    }

    /** @return array{code: string, ton: string, titre: string, texte: string} */
    public static function verdict(Reservation $r, ?Voyage $bus = null): array
    {
        $depart = $r->departAt();
        $quand = __(':date à :heure', ['date' => $depart->format('d/m/Y'), 'heure' => $depart->format('H:i')]);
        $trajet = __($r->villeDepart?->ville) . ' → ' . __($r->villeArrivee?->ville);

        return match (true) {
            $r->isCancelled() => self::v('annule', 'stop', __('Billet annulé'), __('Ce billet a été annulé : il ne permet pas de monter.')),
            $r->statut === Reservation::EN_ATTENTE => self::v('non_confirme', 'stop', __('Paiement non confirmé'), __('Le paiement en ligne de ce billet n\'a pas abouti.')),
            $r->isBoarded() => self::v('deja_monte', 'stop', __('Déjà monté'),
                __('Ce billet a déjà servi à :heure (:nom). Même billet présenté deux fois ?', ['heure' => $r->embarque_le->format('H:i'), 'nom' => $r->embarquePar?->name ?? '—'])),
            $bus && $r->voyage_id !== $bus->id => self::v('mauvais_bus', 'stop', __('Mauvais bus'), __('Ce billet est pour :trajet le :quand.', ['trajet' => $trajet, 'quand' => $quand])),
            $depart->copy()->addHours(self::APRES_HEURES)->isPast() => self::v('parti', 'stop', __('Départ passé'), __('Ce billet était pour le :quand.', ['quand' => $quand])),
            $depart->copy()->subHours(self::AVANT_HEURES)->isFuture() => self::v('trop_tot', 'stop', __('Pas pour ce départ'), __('Ce billet est pour le :quand.', ['quand' => $quand])),
            $r->resteAPayer() > 0 => self::v('a_payer', 'payer', __('À encaisser : :montant DH', ['montant' => self::dh($r->resteAPayer())]),
                $r->isPaid() ? __('Supplément après changement de départ.') : __('Le voyageur n\'a pas encore payé ce billet.')),
            default => self::v('valable', 'ok', __('Billet valable · payé'), __('Siège :siege', ['siege' => $r->num_siege])),
        };
    }

    /** Records the scan (first scan time on the ticket + a line in the scan log) and returns the verdict. */
    public static function noter(Reservation $r, User $par, ?Voyage $bus = null): array
    {
        $verdict = self::verdict($r, $bus);
        if (! $r->scanne_le) {
            $r->forceFill(['scanne_le' => now()])->save();
        }
        Scan::create(['reservation_id' => $r->id, 'user_id' => $par->id, 'voyage_id' => $bus?->id, 'resultat' => $verdict['code']]);

        return $verdict;
    }

    /** What the scanner page shows for a ticket (JSON). */
    public static function fiche(Reservation $r, array $verdict, ?Voyage $bus, User $par): array
    {
        $r->loadMissing(['villeDepart', 'villeArrivee', 'autocar.societe']);

        return [
            'verdict' => $verdict,
            'billet' => [
                'id' => $r->id,
                'passager' => $r->passager(),
                'trajet' => __($r->villeDepart?->ville) . ' → ' . __($r->villeArrivee?->ville),
                'depart' => $r->departAt()->format('d/m/Y H:i'),
                'siege' => $r->num_siege,
                'autocar' => trim(($r->autocar?->societe?->raison_social ?? '') . ' · ' . ($r->autocar?->matricule ?? ''), ' ·'),
                'reste' => $r->resteAPayer(),
                'lien' => route('reservation.admin.show', $r),
            ],
            'urls' => [
                'embarquer' => route('reservation.admin.embarquer', $r),
            ],
            'stats' => self::stats($bus, $par),
        ];
    }

    /** Counters of the scanner page: the bus being checked, and the staff member's cash drawer today. */
    public static function stats(?Voyage $bus, User $par): array
    {
        $caisse = Encaissement::where('user_id', $par->id)->whereDate('created_at', today())
            ->selectRaw('mode, count(*) as n, sum(montant) as total')->groupBy('mode')->get()->keyBy('mode');

        $stats = [
            'caisse' => [
                'especes' => (float) ($caisse['especes']->total ?? 0),
                'carte' => (float) ($caisse['carte']->total ?? 0),
                'n' => (int) $caisse->sum('n'),
            ],
            'bus' => null,
        ];

        if ($bus) {
            $billets = Reservation::where('voyage_id', $bus->id)->where('statut', '!=', Reservation::EN_ATTENTE)->get();
            $stats['bus'] = [
                'billets' => $billets->count(),
                'a_bord' => $billets->whereNotNull('embarque_le')->count(),
                'a_encaisser' => round($billets->sum(fn ($b) => $b->resteAPayer()), 2),
            ];
        }

        return $stats;
    }

    public static function dh(float $montant): string
    {
        return number_format($montant, 2, ',', ' ');
    }

    private static function v(string $code, string $ton, string $titre, string $texte): array
    {
        return compact('code', 'ton', 'titre', 'texte');
    }
}
