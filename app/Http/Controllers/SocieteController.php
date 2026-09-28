<?php

namespace App\Http\Controllers;

use App\Models\Societe;
use App\Models\Voyage;
use App\Http\Requests\StoreSocieteRequest;
use App\Http\Requests\UpdateSocieteRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

class SocieteController extends Controller
{
    public function index()
    {
        $societes = Societe::paginate(10);
        return view('admin.societes.index', compact('societes'));
    }

    public function create()
    {
        return view('admin.societes.create');
    }

    public function store(StoreSocieteRequest $request)
    {
        $formFields = $request->validated();

        if ($request->hasFile('logo')) {
            $formFields['logo'] = $request->file('logo')->store('societes', 'public');
        }
        Societe::create($formFields);

        return redirect()->route('societes.index')->with('success', 'Votre société a été créée avec succès.');
    }

    public function edit(Societe $societe)
    {
        return view('admin.societes.edit', compact('societe'));
    }

    public function update(UpdateSocieteRequest $request, Societe $societe)
    {
        $formFields = $request->validated();

        if ($request->hasFile('logo')) {
            if ($societe->logo) {
                Storage::disk('public')->delete($societe->logo);
            }
            $formFields['logo'] = $request->file('logo')->store('societes', 'public');
        }
        $societe->update($formFields);

        return redirect()->route('societes.index')->with('success', 'Votre société a été modifiée avec succès.');
    }

    public function destroy(Societe $societe)
    {
        if ($societe->autocars()->exists()) {
            return redirect()->route('societes.index')->with('error', 'Impossible de supprimer cette société : elle possède des autocars.');
        }

        try {
            $societe->delete();
        } catch (QueryException $e) {
            return redirect()->route('societes.index')->with('error', "Impossible de supprimer cette société car elle est liée à d'autres données.");
        }

        if ($societe->logo) {
            Storage::disk('public')->delete($societe->logo);
        }

        return redirect()->route('societes.index')->with('success', 'Votre société a été supprimée avec succès.');
    }

    /**
     * Public page: all voyages operated by a company.
     */
    public function showVoyageSociete(Societe $societe)
    {
        $voyages = Voyage::with(['villeDepart', 'villeArrivee', 'typeVoyage', 'autocar'])
            ->whereHas('autocar', fn ($q) => $q->where('societe_id', $societe->id))
            ->orderBy('date_depart')
            ->get();

        return view('client.societes.showVoyageSociete.index', compact('societe', 'voyages'));
    }
}
