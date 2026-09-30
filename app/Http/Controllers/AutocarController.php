<?php

namespace App\Http\Controllers;

use App\Models\Autocar;
use App\Models\AutocarEquipement;
use App\Models\AutocarOption;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreautocarRequest;
use App\Http\Requests\UpdateautocarRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

class AutocarController extends Controller
{
    public function index()
    {
        $autocars = Autocar::with('societe')->paginate(10);
        return view('admin.autocars.index', compact('autocars'));
    }

    public function create()
    {
        return view('admin.autocars.create');
    }

    public function store(StoreautocarRequest $request)
    {
        $formFields = $request->validated();

        if ($request->hasFile('image')) {
            $formFields['image'] = $request->file('image')->store('autocars', 'public');
        }

        // company space: a company account can only add buses to its own company
        if (\App\Support\SocieteScope::$societeId) {
            $formFields['societe_id'] = \App\Support\SocieteScope::$societeId;
        }
        Autocar::create($formFields);

        return redirect()->route('autocars.index')->with('success', 'Votre autocar a été créé avec succès.');
    }

    public function edit(Autocar $autocar)
    {
        return view('admin.autocars.edit', compact('autocar'));
    }

    public function update(UpdateautocarRequest $request, Autocar $autocar)
    {
        $formFields = $request->validated();

        if ($request->hasFile('image')) {
            if ($autocar->image) {
                Storage::disk('public')->delete($autocar->image);
            }
            $formFields['image'] = $request->file('image')->store('autocars', 'public');
        }

        if (\App\Support\SocieteScope::$societeId) {
            $formFields['societe_id'] = \App\Support\SocieteScope::$societeId;
        }
        $autocar->update($formFields);

        return redirect()->route('autocars.index')->with('success', 'Votre autocar a été modifié avec succès.');
    }

    public function destroy(Autocar $autocar)
    {
        if ($autocar->voyages()->exists()) {
            return redirect()->route('autocars.index')->with('error', "Impossible de supprimer cet autocar : il est utilisé par des voyages.");
        }

        try {
            // Its options / équipements links go with it
            DB::transaction(function () use ($autocar) {
                AutocarOption::where('autocar_id', $autocar->id)->delete();
                AutocarEquipement::where('autocar_id', $autocar->id)->delete();
                $autocar->delete();
            });
        } catch (QueryException $e) {
            return redirect()->route('autocars.index')->with('error', "Impossible de supprimer cet autocar car il est lié à des réservations.");
        }

        if ($autocar->image) {
            Storage::disk('public')->delete($autocar->image);
        }

        return redirect()->route('autocars.index')->with('success', 'Votre autocar a été supprimé avec succès.');
    }
}
