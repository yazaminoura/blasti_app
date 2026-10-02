<?php

namespace App\Http\Controllers\Chauffeur;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Illuminate\Http\Request;

class ChauffeurController extends Controller
{
    /**
     * Dashboard for chauffeurs: shows trips assigned to them (or today's trips).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Voyage::with(['villeDepart', 'villeArrivee', 'autocar.societe', 'arrets.ville'])
            ->withCount(['reservations' => fn ($q) => $q->where('statut', '!=', \App\Models\Reservation::ANNULEE)->where('statut', '!=', \App\Models\Reservation::EN_ATTENTE)]);

        // If the user is assigned specifically to trips as chauffeur, show their trips
        if ($user->hasRole('Chauffeur') || $user->hasRole('Compagnie – Chauffeur')) {
            $hasAssigned = Voyage::where('chauffeur_id', $user->id)->exists();
            if ($hasAssigned) {
                $query->where('chauffeur_id', $user->id);
            }
        }

        $onglet = $request->input('statut', 'a_venir');

        if ($onglet === 'passes') {
            $voyages = $query->whereDate('date_depart', '<', today())
                ->orderByDesc('date_depart')->orderByDesc('heure_depart')
                ->paginate(15);
        } else {
            // Upcoming or today
            $voyages = $query->whereDate('date_depart', '>=', today())
                ->orderBy('date_depart')->orderBy('heure_depart')
                ->paginate(15);
        }

        return view('chauffeur.index', compact('voyages', 'onglet'));
    }

    /**
     * Detailed operational sheet for the driver.
     * Strictly anonymized: NO client names, phone numbers, or private data.
     */
    public function show(Voyage $voyage)
    {
        $voyage->load(['villeDepart', 'villeArrivee', 'autocar.societe', 'arrets.ville', 'chauffeur']);
        $stats = $voyage->statsChauffeur();

        return view('chauffeur.show', compact('voyage', 'stats'));
    }

    /**
     * Report or update trip delay (retard).
     */
    public function retard(Request $request, Voyage $voyage)
    {
        $data = $request->validate([
            'retard_minutes' => 'required|integer|min:0|max:360',
            'motif_retard' => 'nullable|string|max:255',
        ]);

        $voyage->update([
            'retard_minutes' => (int) $data['retard_minutes'],
            'motif_retard' => $data['motif_retard'] ? trim($data['motif_retard']) : null,
        ]);

        $message = $data['retard_minutes'] > 0
            ? "Retard de {$data['retard_minutes']} min enregistré. Les passagers et contrôleurs peuvent voir l'estimation révisée."
            : "Le départ est désormais marqué à l'heure normale.";

        return back()->with('success', $message);
    }
}
