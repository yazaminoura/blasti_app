<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModeReglementRequest;
use App\Models\ModeReglement;

class ModeReglementController extends Controller
{
    public function index()
    {
        $modes = ModeReglement::all();
        return view('admin.modeReglements.index', compact('modes'));
    }

    public function create()
    {
        return view('admin.modeReglements.create');
    }

    public function store(ModeReglementRequest $request)
    {
        ModeReglement::create($request->validated());

        return redirect()->route('modeReglements.index')->with('success', 'Mode de règlement ajouté avec succès.');
    }

    public function edit(ModeReglement $modeReglement)
    {
        $mode = $modeReglement;
        return view('admin.modeReglements.edit', compact('mode'));
    }

    public function update(ModeReglementRequest $request, ModeReglement $modeReglement)
    {
        $modeReglement->update($request->validated());

        return redirect()->route('modeReglements.index')->with('success', 'Mode de règlement modifié avec succès.');
    }

    public function destroy(ModeReglement $modeReglement)
    {
        return $this->deleteAndRedirect(
            $modeReglement,
            'modeReglements.index',
            'Mode de règlement supprimé avec succès.',
            'Impossible de supprimer ce mode de règlement car il est utilisé par des réservations.'
        );
    }
}
