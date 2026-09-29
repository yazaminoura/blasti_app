<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\ReservationMail;
use App\Models\Reservation;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The client moves a ticket to another departure (or another seat) of the same trip,
 * until config('safar.modification_heures') hours before boarding. Free of charge;
 * a dearer departure: the difference is paid at boarding; a cheaper one: no refund (said before confirming).
 * The ticket keeps its number and QR code.
 */
class TicketChangeController extends Controller
{
    /** Step 1: other departures of the same trip. Step 2 (?voyage=): seat map of the chosen departure. */
    public function edit(Request $request, Reservation $reservation)
    {
        $this->authorizeChange($reservation);
        if ($redirect = $this->refuseIfTooLate($reservation)) {
            return $redirect;
        }

        $reservation->loadMissing(['villeDepart', 'villeArrivee', 'autocar.societe', 'modeReglement']);
        $from = $reservation->ville_depart_id;
        $to = $reservation->ville_arrivee_id;
        $date = $request->filled('date') ? $request->date('date') : null;

        $departs = Voyage::serving($from, $to, $date)
            ->with(['autocar.societe', 'arrets.ville', 'reservations:id,voyage_id,num_siege,arret_depart_id,arret_arrivee_id'])
            ->take(30)->get()
            ->map(function (Voyage $voyage) use ($from, $to, $reservation) {
                $segment = $voyage->segmentFor($from, $to);
                if (! $segment || $segment[0]->passage_at->isPast()) {
                    return null;
                }

                return (object) [
                    'voyage' => $voyage,
                    'depart' => $segment[0],
                    'arrivee' => $segment[1],
                    'prix' => $voyage->segmentPrice(...$segment),
                    'libres' => max(0, (int) $voyage->autocar?->nbr_siege - count($voyage->seatsTaken($segment[0], $segment[1], $reservation->id))),
                    'actuel' => $voyage->id === $reservation->voyage_id,
                ];
            })
            ->filter()->values();

        // step 2: the seat map of the chosen departure
        $choix = $request->filled('voyage') ? $departs->firstWhere('voyage.id', $request->integer('voyage')) : null;
        $reservedSeats = $choix ? $choix->voyage->seatsTaken($choix->depart, $choix->arrivee, $reservation->id) : [];

        return view('client.reservations.changer', compact('reservation', 'departs', 'choix', 'reservedSeats', 'date'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->authorizeChange($reservation);
        if ($redirect = $this->refuseIfTooLate($reservation)) {
            return $redirect;
        }

        $request->validate([
            'voyage_id' => ['required', 'integer', 'exists:voyages,id'],
            'seats' => ['required', 'array', 'size:1'],
            'seats.*' => ['required', 'integer', 'min:1'],
        ], [
            'seats.required' => __('Veuillez choisir un siège.'),
            'seats.size' => __('Veuillez choisir un seul siège.'),
        ]);
        $seat = (int) $request->seats[0];

        try {
            DB::transaction(function () use ($request, $reservation, $seat) {
                $voyage = Voyage::with(['autocar', 'arrets'])->lockForUpdate()->findOrFail($request->voyage_id);
                $segment = $voyage->segmentFor($reservation->ville_depart_id, $reservation->ville_arrivee_id);
                if (! $segment) {
                    throw new \DomainException(__('Ce départ ne fait pas votre trajet.'));
                }
                [$depart, $arrivee] = $segment;

                if ($depart->passage_at->isPast()) {
                    throw new \DomainException(__('Ce voyage est déjà parti, la réservation est impossible.'));
                }
                if (! $voyage->autocar || $seat > $voyage->autocar->nbr_siege) {
                    throw new \DomainException(__("Le siège choisi n'existe pas dans cet autocar."));
                }
                if (in_array($seat, $voyage->seatsTaken($depart, $arrivee, $reservation->id), true)) {
                    throw new \DomainException(__("Ce siège vient d'être réservé par un autre client. Veuillez en choisir un autre."));
                }

                $reservation->forceFill([
                    'voyage_id' => $voyage->id,
                    'num_siege' => $seat,
                    'siege_actif' => $seat,
                    'date_depart' => $depart->passage_at->toDateString(),
                    'date_arrivee' => $arrivee->passage_at->toDateString(),
                    'heure_depart' => $depart->passage_at->format('H:i:s'),
                    'heure_arrivee' => $arrivee->passage_at->format('H:i:s'),
                    'autocar_id' => $voyage->autocar_id,
                    'type_voyage_id' => $voyage->type_voyage_id,
                    'arret_depart_id' => $depart->id,
                    'arret_arrivee_id' => $arrivee->id,
                    'prix' => $voyage->segmentPrice($depart, $arrivee),
                    'modifiee_le' => now(),
                    // the reminder is sent again for the new day
                    'rappel_envoye_le' => null,
                ])->save();
            });
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $reservation->refresh();
        ReservationMail::sendTo($reservation, 'modifiee');

        return redirect()->route('ticket.show', $reservation->id)->with('success', $reservation->resteAPayer() > 0 && $reservation->isPaid()
            ? __('Billet modifié ! Supplément de :montant DH à régler à l\'embarquement. Votre nouveau billet vous a été envoyé par e-mail.', ['montant' => number_format($reservation->resteAPayer(), 2, ',', ' ')])
            : __('Billet modifié ! Votre nouveau billet vous a été envoyé par e-mail.'));
    }

    private function authorizeChange(Reservation $reservation): void
    {
        abort_unless($reservation->user_id === auth()->id(), 403);
    }

    private function refuseIfTooLate(Reservation $reservation)
    {
        if ($reservation->canBeChangedByClient()) {
            return null;
        }

        return redirect()->route('ticket.show', $reservation->id)->with('error', $reservation->isCancelled()
            ? __('Ce billet est annulé.')
            : __('Un billet peut être modifié jusqu\'à :h heures avant le départ.', ['h' => config('safar.modification_heures')]));
    }
}
