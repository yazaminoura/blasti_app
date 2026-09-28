<?php

namespace App\Http\Controllers\Client\Profile;

use App\Http\Controllers\Controller;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = auth()->user()
            ->reservations()
            ->withoutGlobalScope('active') // cancelled tickets stay visible in the history
            ->with(['villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe'])
            // active trips first (next ones first), then past, then cancelled
            ->orderByRaw("statut = 'annulee' asc")
            ->orderByRaw('date_depart < ? asc', [today()->toDateString()])
            ->orderBy('date_depart')
            ->orderBy('heure_depart')
            ->paginate(10);

        return view('client.profile.reservations.index', compact('reservations'));
    }
}
