<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Http\Requests\EquipementRequest;

class EquipementController extends Controller
{
    public function index()
    {
        $equipements = Equipement::paginate(10);
        return view('admin.equipements.index', compact('equipements'));
    }

    public function create()
    {
        return view('admin.equipements.create');
    }

    public function store(EquipementRequest $request)
    {
        Equipement::create($request->validated());

        return redirect()->route('equipements.index')->with('success', 'Votre équipement a été créé avec succès.');
    }

    public function edit(Equipement $equipement)
    {
        return view('admin.equipements.edit', compact('equipement'));
    }

    public function update(EquipementRequest $request, Equipement $equipement)
    {
        $equipement->update($request->validated());

        return redirect()->route('equipements.index')->with('success', 'Votre équipement a été modifié avec succès.');
    }

    public function destroy(Equipement $equipement)
    {
        return $this->deleteAndRedirect(
            $equipement,
            'equipements.index',
            'Votre équipement a été supprimé avec succès.',
            "Impossible de supprimer cet équipement car il est lié à d'autres données."
        );
    }
}
