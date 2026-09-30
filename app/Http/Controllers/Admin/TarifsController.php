<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parametre;
use App\Support\Tarif;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Admin > Paramètres > Tarifs selon la date: the price goes up as the departure gets closer (App\Support\Tarif). */
class TarifsController extends Controller
{
    public function edit()
    {
        return view('admin.tarifs.edit', ['regles' => Tarif::regles()]);
    }

    public function update(Request $request)
    {
        // empty lines of the form are ignored
        $lignes = collect($request->input('regles', []))
            ->filter(fn ($r) => filled($r['jours'] ?? null) || filled($r['valeur'] ?? null))
            ->values()->all();
        $request->merge(['regles' => $lignes]);

        $request->validate([
            'regles' => ['array', 'max:10'],
            'regles.*.jours' => ['required', 'integer', 'min:0', 'max:365', 'distinct'],
            'regles.*.type' => ['required', Rule::in(array_keys(Tarif::TYPES))],
            'regles.*.valeur' => ['required', 'numeric', 'min:0.01', 'max:100000'],
        ], [
            'regles.*.jours.required' => 'Indiquez le nombre de jours de chaque ligne.',
            'regles.*.jours.distinct' => 'Deux lignes ont le même nombre de jours.',
            'regles.*.valeur.required' => 'Indiquez l\'augmentation de chaque ligne.',
            'regles.*.valeur.min' => 'L\'augmentation doit être plus grande que 0.',
        ]);

        $regles = collect($lignes)->map(fn ($r) => [
            'jours' => (int) $r['jours'], 'type' => $r['type'], 'valeur' => round((float) $r['valeur'], 2),
        ])->sortByDesc('jours')->values()->all();

        $parametre = Parametre::actuel();
        $parametre->majorations = $regles;
        $parametre->save();

        return redirect()->route('admin.tarifs.edit')->with('success', $regles
            ? 'Tarifs par défaut enregistrés : ils s\'appliquent tout de suite aux voyages sans tarifs propres (site et guichet).'
            : 'Aucune augmentation par défaut : les voyages sans tarifs propres gardent le même prix quelle que soit la date.');
    }
}
