<?php

namespace App\Http\Controllers;

use App\Mail\ReservationMail;
use App\Models\Encaissement;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\Scan;
use App\Models\Ville;
use App\Models\Voyage;
use App\Support\Controle;
use Illuminate\Http\Request;

/**
 * Admin side of reservations. Client booking lives in Client\ReservationController.
 */
class ReservationController extends Controller
{
    /** Filters that list cancelled reservations (hidden by default). */
    private const CANCELLED_FILTERS = ['annulee', 'a_rembourser'];

    public function indexAdmin(Request $request)
    {
        Reservation::expirePendingPayments();
        $filters = $request->only(Reservation::FILTERS);

        $query = Reservation::filter($filters);
        if (in_array($filters['statut'] ?? null, self::CANCELLED_FILTERS, true)) {
            $query->withoutGlobalScope('active');
        }

        $totals = [
            'count'   => (clone $query)->count(),
            'revenue' => (clone $query)->sum('prix') + (clone $query)->sum('frais') - (clone $query)->sum('remise'),
        ];

        $reservations = $query
            ->with(['user', 'villeDepart', 'villeArrivee', 'modeReglement'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'filters'      => $filters,
            'totals'       => $totals,
            'villes'       => Ville::orderBy('ville')->get(),
            'modes'        => ModeReglement::orderBy('mode_reglement')->get(),
            'toRefund'     => Reservation::withoutGlobalScope('active')->filter(['statut' => 'a_rembourser'])->count(),
        ]);
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'typeVoyage', 'autocar.societe', 'voyage', 'embarquePar', 'encaissements.user', 'scans.user']);

        return view('admin.reservations.show', compact('reservation'));
    }

    /**
     * Cancel a reservation: it stays in the history and its seat becomes free again.
     */
    public function destroy(Reservation $reservation)
    {
        if ($reservation->isCancelled()) {
            return back()->with('error', 'Cette réservation est déjà annulée.');
        }

        $reservation->cancel('admin');
        ReservationMail::sendTo($reservation, 'annulee');

        return redirect()->route('reservation.admin.show', $reservation)
            ->with('success', "La réservation #{$reservation->id} a été annulée, le siège {$reservation->num_siege} est de nouveau disponible."
                . ($reservation->isPaid() ? ' Pensez au remboursement.' : ''));
    }

    /**
     * Controller at the bus door: camera QR scanner or a ticket number typed by hand. The page stays open and
     * checks each ticket in place (see scan()); choosing the bus being checked turns on the "wrong bus" check.
     */
    public function scanner(Request $request)
    {
        // without JavaScript: ?billet=125 opens the ticket check page
        if ($request->filled('billet')) {
            $billet = Reservation::withoutGlobalScope('active')->find($request->integer('billet'));

            return $billet
                ? redirect()->to($billet->verificationUrl())
                : back()->with('error', 'Aucun billet n° ' . $request->integer('billet') . ' (ou il appartient à une autre compagnie).');
        }

        $voyages = Voyage::with(['villeDepart', 'villeArrivee', 'autocar'])
            ->whereDate('date_depart', '>=', today()->subDay())->whereDate('date_depart', '<=', today()->addDay())
            ->orderBy('date_depart')->orderBy('heure_depart')->get();
        $bus = $request->filled('voyage') ? $voyages->firstWhere('id', $request->integer('voyage')) : null;

        return view('admin.reservations.scanner', [
            'voyages' => $voyages,
            'bus' => $bus,
            'stats' => Controle::stats($bus, $request->user()),
        ]);
    }

    /** One scan (JSON for the scanner page): reads the code, checks the ticket, logs the scan. */
    public function scan(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:2000', 'voyage' => 'nullable|integer']);
        $bus = ! empty($data['voyage']) ? Voyage::find($data['voyage']) : null;
        $user = $request->user();

        $numero = Controle::numeroDuCode($data['code']);
        // the company scope hides other companies' tickets: same answer as an unknown number
        $reservation = $numero ? Reservation::withoutGlobalScope('active')->find($numero) : null;

        if (! $reservation) {
            Scan::create(['user_id' => $user->id, 'voyage_id' => $bus?->id, 'resultat' => $numero ? 'introuvable' : 'illisible']);

            return response()->json([
                'verdict' => [
                    'code' => $numero ? 'introuvable' : 'illisible', 'ton' => 'stop',
                    'titre' => $numero ? 'Billet introuvable' : 'QR code non reconnu',
                    'texte' => $numero ? "Aucun billet n° {$numero} (ou il appartient à une autre compagnie)." : 'Ce n\'est pas un billet ' . config('safar.nom') . ', ou le lien a été modifié.',
                ],
                'billet' => null,
                'stats' => Controle::stats($bus, $user),
            ]);
        }

        return response()->json(Controle::fiche($reservation, Controle::noter($reservation, $user, $bus), $bus, $user));
    }

    /** Payment received at the station / agency or by the controller (cash or card terminal). */
    public function payer(Request $request, Reservation $reservation)
    {
        $mode = $request->validate(['mode' => 'nullable|in:' . implode(',', array_keys(Encaissement::MODES))])['mode'] ?? 'especes';

        // unpaid ticket, or supplement still due after the client moved it to a dearer departure
        if ($reservation->isCancelled() || $reservation->resteAPayer() <= 0) {
            return back()->with('error', 'Cette réservation est déjà payée ou annulée.');
        }

        $montant = $reservation->encaisser($request->user(), $mode);

        return back()->with('success', "Réservation #{$reservation->id} : " . Controle::dh($montant) . ' DH encaissés (' . Encaissement::MODES[$mode] . ').');
    }

    /**
     * The controller lets the traveller in (scanner page or ticket check page). With "mode", the money still
     * due is collected in the same click. Tickets never scanned on a bus where others were scanned count as
     * no-shows (User::absences).
     */
    public function embarquer(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'mode' => 'nullable|in:' . implode(',', array_keys(Encaissement::MODES)),
            'voyage' => 'nullable|integer',
        ]);
        $bus = ! empty($data['voyage']) ? Voyage::find($data['voyage']) : null;
        $user = $request->user();

        $verdict = Controle::verdict($reservation, $bus);
        $erreur = match (true) {
            $verdict['ton'] === 'stop' => $verdict['titre'] . ' : ' . $verdict['texte'],
            $verdict['ton'] === 'payer' && empty($data['mode']) => 'Encaissez d\'abord ' . Controle::dh($reservation->resteAPayer()) . ' DH.',
            default => null,
        };
        if ($erreur) {
            return $request->expectsJson()
                ? response()->json(['erreur' => $erreur] + Controle::fiche($reservation, $verdict, $bus, $user), 422)
                : back()->with('error', $erreur);
        }

        $encaisse = ! empty($data['mode']) ? $reservation->encaisser($user, $data['mode']) : 0;
        $reservation->embarquer($user);
        $message = "Siège {$reservation->num_siege} : voyageur embarqué" . ($encaisse > 0 ? ' · ' . Controle::dh($encaisse) . ' DH encaissés.' : '.');

        return $request->expectsJson()
            ? response()->json(['message' => $message] + Controle::fiche($reservation->refresh(), Controle::verdict($reservation, $bus), $bus, $user))
            : back()->with('success', $message);
    }

    /** Refund done for a cancelled, paid reservation. */
    public function rembourser(Reservation $reservation)
    {
        if (! $reservation->needsRefund()) {
            return back()->with('error', 'Aucun remboursement en attente pour cette réservation.');
        }

        $reservation->forceFill(['rembourse_le' => now()])->save();

        return back()->with('success', "Remboursement de " . number_format($reservation->amountToRefund(), 2, ',', ' ') . " DH enregistré pour la réservation #{$reservation->id}.");
    }
}
