<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Autocar;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;

class DashboardController extends Controller
{
    public function index()
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();
        // Same point in last month: on the 10th we compare 1st–10th with 1st–10th, not with the whole month
        $lastMonthSoFar = now()->subMonthNoOverflow();
        $today = today()->toDateString();

        $thisPeriod = fn ($query) => $query->where('created_at', '>=', $thisMonth);
        $lastPeriod = fn ($query) => $query->where('created_at', '>=', $lastMonth)->where('created_at', '<', $lastMonthSoFar);
        $revenue = fn ($query) => (float) $query->sum(\Illuminate\Support\Facades\DB::raw('prix + frais - remise'));

        // ---- KPI cards (value + evolution this month vs the same days of last month) ----
        $clients = fn () => User::where('isadmin', 0);
        $totalUsers = $clients()->count();
        $usersTrend = $this->trend($thisPeriod($clients())->count(), $lastPeriod($clients())->count());

        $totalVoyages = Voyage::count();
        $upcomingVoyages = Voyage::whereDate('date_depart', '>=', $today)->count();

        $totalReservations = Reservation::count();
        $reservationsTrend = $this->trend(
            $thisPeriod(Reservation::query())->count(),
            $lastPeriod(Reservation::query())->count()
        );

        // Revenue = price + fees everywhere (same as the reservations list)
        $totalRevenue = $revenue(Reservation::query());
        $revenueThisMonth = $revenue($thisPeriod(Reservation::query()));
        $revenueTrend = $this->trend($revenueThisMonth, $revenue($lastPeriod(Reservation::query())));

        $recentReservations = Reservation::with(['user', 'villeDepart', 'villeArrivee'])->latest()->take(5)->get();

        // ---- Reservations per month (last 6 months), grouped in PHP: works on any database ----
        $start = now()->startOfMonth()->subMonths(5);
        $reservationsByMonth = Reservation::where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn ($date) => $date->format('Y-m'));

        $months = [];
        $reservationCounts = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $months[] = $month->translatedFormat('M Y');
            $reservationCounts[] = $reservationsByMonth[$month->format('Y-m')] ?? 0;
        }

        // ---- Top 5 destinations ----
        $topDestinations = Reservation::join('villes', 'reservations.ville_arrivee_id', '=', 'villes.id')
            ->selectRaw('villes.ville as name, COUNT(*) as count')
            ->groupBy('villes.ville')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // ---- Fleet & upcoming departures ----
        $totalAutocars = Autocar::count();
        $busyAutocars = Autocar::whereHas('voyages', fn ($q) => $q->whereDate('date_depart', '>=', $today))->count();

        $nextDepartures = Voyage::with(['villeDepart', 'villeArrivee', 'autocar'])
            ->withCount('reservations')
            ->whereDate('date_depart', '>=', $today)
            ->orderBy('date_depart')
            ->orderBy('heure_depart')
            ->take(5)
            ->get();

        $upcomingForOccupancy = Voyage::with('autocar')
            ->withCount('reservations')
            ->whereDate('date_depart', '>=', $today)
            ->get();
        $averageOccupancy = $upcomingForOccupancy->isEmpty()
            ? 0
            : (int) round($upcomingForOccupancy->avg(fn ($voyage) => $voyage->occupancyRate()));

        return view('admin.dashboard', compact(
            'totalUsers',
            'usersTrend',
            'totalVoyages',
            'upcomingVoyages',
            'totalReservations',
            'reservationsTrend',
            'totalRevenue',
            'revenueThisMonth',
            'revenueTrend',
            'recentReservations',
            'months',
            'reservationCounts',
            'topDestinations',
            'totalAutocars',
            'busyAutocars',
            'nextDepartures',
            'averageOccupancy'
        ));
    }

    /**
     * Evolution in % between two periods; null when there is nothing to compare with.
     */
    private function trend(float|int $current, float|int $previous): ?int
    {
        if ($previous == 0) {
            return null;
        }

        return (int) round(($current - $previous) * 100 / $previous);
    }
}
