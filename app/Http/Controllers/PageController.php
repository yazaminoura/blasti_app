<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Societe;
use App\Models\Ville;
use App\Models\Voyage;

/** Public information pages: destinations, bus companies, help, about. */
class PageController extends Controller
{
    public function destinations()
    {
        $villes = Ville::has('voyagesDepart')->orHas('voyagesArrivee')
            ->withCount([
                'voyagesDepart as departs_count' => fn ($q) => $q->bookable(),
                'voyagesArrivee as arrivees_count' => fn ($q) => $q->bookable(),
            ])
            ->orderByDesc('departs_count')->orderBy('ville')
            ->get();

        // cheapest upcoming price and the cities reachable from each city
        $routes = Voyage::bookable()->with('villeArrivee')->get(['id', 'ville_depart_id', 'ville_arrivee_id', 'prix', 'date_depart', 'heure_depart'])
            ->groupBy('ville_depart_id');

        return view('pages.destinations', compact('villes', 'routes'));
    }

    public function compagnies()
    {
        $societes = Societe::has('autocars')
            ->withCount('autocars')
            ->with(['autocars.equipements', 'autocars.options'])
            ->orderBy('raison_social')
            ->get()
            ->each(function (Societe $societe) {
                $busIds = $societe->autocars->pluck('id');
                $societe->departs_count = Voyage::bookable()->whereIn('autocar_id', $busIds)->count();
                $societe->prix_min = Voyage::bookable()->whereIn('autocar_id', $busIds)->min('prix');
                $societe->services = $societe->autocars->flatMap(fn ($a) => $a->equipements->pluck('equipement')->merge($a->options->pluck('option')))->unique()->values();
            });

        return view('pages.compagnies', compact('societes'));
    }

    public function aide()
    {
        return view('pages.aide');
    }

    /** Legal pages: CGV, privacy policy, legal notice (texts in resources/views/pages/legal). */
    public function legal(string $page)
    {
        abort_unless(in_array($page, ['conditions', 'confidentialite', 'mentions-legales'], true), 404);

        return view('pages.legal.' . $page);
    }

    public function aPropos()
    {
        $stats = [
            'villes' => Ville::has('voyagesDepart')->orHas('voyagesArrivee')->count(),
            'departs' => Voyage::bookable()->count(),
            'societes' => Societe::has('autocars')->count(),
            'billets' => Reservation::count(),
        ];

        return view('pages.a-propos', compact('stats'));
    }
}
