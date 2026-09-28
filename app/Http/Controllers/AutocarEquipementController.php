<?php

namespace App\Http\Controllers;

use App\Models\AutocarEquipement;
use App\Http\Requests\AutocarEquipementRequest;
use App\Models\Autocar;
use App\Models\Equipement;

class AutocarEquipementController extends Controller
{
    public function index()
    {
        $autocarequipements = AutocarEquipement::with(['autocar', 'equipement'])->paginate(10);
        return view('admin.autocarequipements.index', compact('autocarequipements'));
    }

    public function create()
    {
        $autocars = Autocar::all();
        $equipements = Equipement::all();

        return view('admin.autocarequipements.create', compact('autocars', 'equipements'));
    }

    public function store(AutocarEquipementRequest $request)
    {
        AutocarEquipement::create($request->validated());

        return redirect()->route('autocarequipements.index')->with('success', "L'équipement a été associé à l'autocar avec succès.");
    }

    public function edit(AutocarEquipement $autocarequipement)
    {
        $autocars = Autocar::all();
        $equipements = Equipement::all();

        return view('admin.autocarequipements.edit', compact('autocarequipement', 'autocars', 'equipements'));
    }

    public function update(AutocarEquipementRequest $request, AutocarEquipement $autocarequipement)
    {
        $autocarequipement->update($request->validated());

        return redirect()->route('autocarequipements.index')->with('success', 'Association modifiée avec succès.');
    }

    public function destroy(AutocarEquipement $autocarequipement)
    {
        $autocarequipement->delete();
        return redirect()->route('autocarequipements.index')->with('success', 'Association supprimée avec succès.');
    }
}
