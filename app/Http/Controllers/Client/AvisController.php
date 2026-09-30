<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\Reservation;
use Illuminate\Http\Request;

/** The client rates his trip (1 to 5 stars + comment) once it is over: one review per ticket. */
class AvisController extends Controller
{
    public function create(Reservation $reservation)
    {
        $this->check($reservation);
        $reservation->loadMissing(['villeDepart', 'villeArrivee', 'autocar.societe']);

        return view('client.reservations.avis', compact('reservation'));
    }

    public function store(Request $request, Reservation $reservation)
    {
        $this->check($reservation);
        $data = $request->validate([
            'note' => 'required|integer|between:1,5',
            'commentaire' => 'nullable|string|max:1000',
        ], ['note.required' => __('Choisissez une note de 1 à 5 étoiles.')]);

        Avis::create($data + [
            'reservation_id' => $reservation->id,
            'user_id' => $request->user()->id,
            'societe_id' => $reservation->autocar?->societe_id,
        ]);

        return redirect()->route('client.profile.reservations.index')->with('success', __('Merci pour votre avis ! Il aide les autres voyageurs à choisir.'));
    }

    private function check(Reservation $reservation): void
    {
        abort_unless($reservation->user_id === auth()->id(), 403);
        abort_unless($reservation->peutEtreNote(), 404, __('Ce voyage ne peut pas (ou plus) être noté.'));
    }
}
