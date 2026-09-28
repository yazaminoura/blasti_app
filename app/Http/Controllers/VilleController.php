<?php

namespace App\Http\Controllers;

use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VilleController extends Controller
{
    public function index()
    {
        $villes = Ville::orderBy('ville')->get();
        return view('admin.villes.index', compact('villes'));
    }

    public function create()
    {
        return view('admin.villes.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateVille($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('villes', 'public');
        }
        Ville::create($data);

        return redirect()->route('villes.index')->with('success', 'Ville ajoutée avec succès');
    }

    public function edit(Ville $ville)
    {
        return view('admin.villes.edit', compact('ville'));
    }

    public function update(Request $request, Ville $ville)
    {
        $data = $this->validateVille($request, $ville);
        if ($request->hasFile('image')) {
            if ($ville->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($ville->image);
            }
            $data['image'] = $request->file('image')->store('villes', 'public');
        }
        $ville->update($data);

        return redirect()->route('villes.index')->with('success', 'Ville modifiée avec succès');
    }

    public function destroy(Ville $ville)
    {
        return $this->deleteAndRedirect(
            $ville,
            'villes.index',
            'Ville supprimée avec succès',
            'Impossible de supprimer cette ville car elle est utilisée par des voyages ou des réservations.'
        );
    }

    private function validateVille(Request $request, ?Ville $ville = null): array
    {
        $data = $request->validate([
            'ville' => [
                'required', 'string', 'min:2', 'max:100', 'regex:/^[^\d]+$/u',
                Rule::unique('villes', 'ville')->ignore($ville?->id),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ], [
            'image.image'    => 'Le fichier doit être une image.',
            'image.mimes'    => 'La photo doit être au format JPG, PNG ou WEBP.',
            'image.max'      => 'La photo ne doit pas dépasser 3 Mo.',
            'ville.required' => 'Le nom de la ville est obligatoire.',
            'ville.regex'    => 'Le nom de la ville ne doit pas contenir de chiffres.',
            'ville.unique'   => 'Cette ville existe déjà.',
        ]);
        unset($data['image']); // stored separately (uploaded file)

        return $data;
    }
}
