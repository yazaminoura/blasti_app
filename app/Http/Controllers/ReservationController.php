<?php

namespace App\Http\Controllers;

use App\Mail\ReservationMail;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\Ville;
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
            'revenue' => (clone $query)->sum('prix') + (clone $query)->sum('frais'),
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
        $reservation->load(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'typeVoyage', 'autocar.societe', 'voyage']);

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

    /** Payment received (at the station / agency). */
    public function payer(Reservation $reservation)
    {
        // unpaid ticket, or supplement still due after the client moved it to a dearer departure
        if ($reservation->isCancelled() || $reservation->resteAPayer() <= 0) {
            return back()->with('error', 'Cette réservation est déjà payée ou annulée.');
        }

        $reservation->markPaid('guichet');

        return back()->with('success', "Réservation #{$reservation->id} marquée comme payée.");
    }

    /**
     * The controller scanned the QR code and lets the traveller in (from the ticket check page).
     * Tickets never scanned on a bus where others were scanned count as no-shows (User::absences).
     */
    public function embarquer(Reservation $reservation)
    {
        if ($reservation->isCancelled() || $reservation->statut === Reservation::EN_ATTENTE) {
            return back()->with('error', 'Billet non valable : embarquement refusé.');
        }
        if ($reservation->resteAPayer() > 0) {
            return back()->with('error', 'Encaissez d\'abord ' . number_format($reservation->resteAPayer(), 2, ',', ' ') . ' DH.');
        }

        $reservation->forceFill(['embarque_le' => $reservation->embarque_le ?? now()])->save();

        return back()->with('success', "Siège {$reservation->num_siege} : voyageur embarqué.");
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
