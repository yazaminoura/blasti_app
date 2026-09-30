<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

/**
 * Red flags of the bus door: a controller scanned an unpaid ticket ("À encaisser"), the bus left,
 * and the ticket was never paid nor boarded. Either the traveller turned back, or the cash was taken
 * without being recorded: the traveller is asked, the controller's count is compared with the others.
 */
class AlertesController extends Controller
{
    public function index(Request $request)
    {
        $jours = in_array((int) $request->jours, [7, 30, 90], true) ? (int) $request->jours : 30;

        $alertes = self::query($jours)
            ->with(['villeDepart', 'villeArrivee', 'user', 'autocar.societe',
                'scans' => fn ($q) => $q->where('resultat', 'a_payer')->with('user')])
            ->orderByDesc('date_depart')->orderByDesc('heure_depart')
            ->paginate(20)->withQueryString();

        // per controller: scanned "to pay" then never paid, on the whole period
        $parControleur = self::query($jours)->with(['scans' => fn ($q) => $q->where('resultat', 'a_payer')->with('user')])->get()
            ->groupBy(fn ($r) => $r->scans->first()?->user?->name ?? '—')
            ->map->count()->sortDesc();

        return view('admin.alertes.index', compact('alertes', 'parControleur', 'jours'));
    }

    /** Unpaid, not boarded, bus already left, and scanned "À encaisser" by someone. */
    public static function query(int $jours = 30)
    {
        return Reservation::query()
            ->whereNull('paye_le')->whereNull('embarque_le')
            ->whereDate('date_depart', '>=', today()->subDays($jours))
            ->where(fn ($q) => $q->whereDate('date_depart', '<', today())
                ->orWhere(fn ($q) => $q->whereDate('date_depart', today())->where('heure_depart', '<', now()->format('H:i:s'))))
            ->whereHas('scans', fn ($q) => $q->where('resultat', 'a_payer'));
    }
}
