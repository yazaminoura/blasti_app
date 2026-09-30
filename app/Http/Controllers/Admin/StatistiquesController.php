<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Sales dashboard: tickets sold, revenue (booked / really cashed), amount still to collect, discounts,
 * cancellations and the platform commission (config safar.commission_pourcent), by company, route and month.
 * A company account (users.societe_id) only sees its own buses.
 */
class StatistiquesController extends Controller
{
    public function index(Request $request)
    {
        $du = $request->filled('du') ? Carbon::parse($request->input('du'))->startOfDay() : now()->startOfMonth();
        $au = $request->filled('au') ? Carbon::parse($request->input('au'))->endOfDay() : now()->endOfDay();
        $societe = $request->user()->societe_id;
        $taux = (float) config('safar.commission_pourcent');

        $scope = fn ($q) => $q->when($societe, fn ($q, $id) => $q->whereHas('autocar', fn ($a) => $a->where('societe_id', $id)));

        // tickets booked in the period (cancelled ones apart)
        $ventes = Reservation::with(['autocar.societe:id,raison_social', 'villeDepart:id,ville', 'villeArrivee:id,ville'])
            ->tap($scope)->whereBetween('created_at', [$du, $au])->get();
        $annulees = Reservation::withoutGlobalScope('active')->tap($scope)
            ->where('statut', Reservation::ANNULEE)->whereBetween('annulee_le', [$du, $au])->count();
        $encaisse = (float) Reservation::withoutGlobalScope('active')->tap($scope)
            ->whereBetween('paye_le', [$du, $au])->sum('montant_paye');

        $ca = $ventes->sum(fn ($r) => $r->total());
        $kpis = [
            'billets' => $ventes->count(),
            'commandes' => $ventes->pluck('commande')->filter()->unique()->count() ?: $ventes->count(),
            'ca' => $ca,
            'encaisse' => $encaisse,
            'a_encaisser' => $ventes->sum(fn ($r) => $r->resteAPayer()),
            'remises' => (float) $ventes->sum('remise'),
            'annulations' => $annulees,
            'taux_annulation' => $ventes->count() + $annulees > 0 ? round($annulees * 100 / ($ventes->count() + $annulees), 1) : 0,
            'commission' => round($ca * $taux / 100, 2),
        ];
        $kpis['panier'] = $kpis['commandes'] ? round($ca / $kpis['commandes'], 2) : 0;

        $parSociete = $ventes->groupBy(fn ($r) => $r->autocar?->societe?->raison_social ?? '—')
            ->map(fn ($g) => ['billets' => $g->count(), 'ca' => $g->sum(fn ($r) => $r->total()), 'a_encaisser' => $g->sum(fn ($r) => $r->resteAPayer())])
            ->map(fn ($l) => $l + ['commission' => round($l['ca'] * $taux / 100, 2)])
            ->sortByDesc('ca');

        $parTrajet = $ventes->groupBy(fn ($r) => ($r->villeDepart?->ville ?? '?') . ' → ' . ($r->villeArrivee?->ville ?? '?'))
            ->map(fn ($g) => ['billets' => $g->count(), 'ca' => $g->sum(fn ($r) => $r->total())])
            ->sortByDesc('billets')->take(10);

        // last 12 months (whatever the period chosen)
        $mois = collect(range(11, 0))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonths($i)->format('Y-m') => ['billets' => 0, 'ca' => 0.0]]);
        Reservation::tap($scope)->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->get(['created_at', 'prix', 'frais', 'remise'])
            ->each(function ($r) use (&$mois) {
                $k = $r->created_at->format('Y-m');
                if ($mois->has($k)) {
                    $m = $mois[$k];
                    $mois[$k] = ['billets' => $m['billets'] + 1, 'ca' => $m['ca'] + $r->total()];
                }
            });

        return view('admin.statistiques.index', compact('du', 'au', 'kpis', 'parSociete', 'parTrajet', 'mois', 'taux'));
    }
}
