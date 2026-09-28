<?php

namespace App\Http\Controllers;

use App\Models\TypeVoyage;
use App\Http\Requests\TypeVoyageRequest;

class TypeVoyageController extends Controller
{
    public function index()
    {
        $types = TypeVoyage::all();
        return view('admin.type_voyages.index', compact('types'));
    }

    public function create()
    {
        return view('admin.type_voyages.create');
    }

    public function store(TypeVoyageRequest $request)
    {
        TypeVoyage::create($request->validated());

        return redirect()->route('type_voyages.index')->with('success', 'Type ajouté avec succès');
    }

    public function edit(TypeVoyage $type_voyage)
    {
        return view('admin.type_voyages.edit', compact('type_voyage'));
    }

    public function update(TypeVoyageRequest $request, TypeVoyage $type_voyage)
    {
        $type_voyage->update($request->validated());

        return redirect()->route('type_voyages.index')->with('success', 'Type modifié avec succès');
    }

    public function destroy(TypeVoyage $type_voyage)
    {
        return $this->deleteAndRedirect(
            $type_voyage,
            'type_voyages.index',
            'Type supprimé avec succès',
            'Impossible de supprimer ce type car il est utilisé par des voyages ou des réservations.'
        );
    }
}
