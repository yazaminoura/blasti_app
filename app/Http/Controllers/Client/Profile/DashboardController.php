<?php

namespace App\Http\Controllers\Client\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** Client home: next trip, a few numbers and the latest bookings. */
    public function index()
    {
        $user = auth()->user();
        $today = today()->toDateString();
        $reservations = fn () => $user->reservations()->with(['villeDepart', 'villeArrivee', 'autocar.societe', 'modeReglement']);

        $nextTrip = $reservations()
            ->whereDate('date_depart', '>=', $today)
            ->orderBy('date_depart')->orderBy('heure_depart')
            ->first();

        $stats = [
            'upcoming' => $user->reservations()->whereDate('date_depart', '>=', $today)->count(),
            'done' => $user->reservations()->whereDate('date_depart', '<', $today)->count(),
            'spent' => (float) $user->reservations()->sum(DB::raw('prix + frais - remise')),
            'favorites' => $user->wishlists()->count(),
        ];

        $recent = $reservations()->latest()->take(4)->get();

        // Missing profile fields, to invite the client to complete them
        $missing = collect(['telephone' => 'téléphone', 'ville' => 'ville', 'adresse' => 'adresse'])
            ->filter(fn ($label, $field) => blank($user->$field))
            ->values();

        return view('client.profile.dashboard.index', compact('user', 'nextTrip', 'stats', 'recent', 'missing'));
    }
}
