<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parametre;
use App\Support\BrandImages;
use Illuminate\Http\Request;

/** Displayed name + main color (site and logo), stored in the single "parametres" row. */
class ApparenceController extends Controller
{
    public function edit()
    {
        return view('admin.apparence.edit', [
            'parametre' => Parametre::actuel(),
            'palette' => config('safar.palette'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:60',
            'couleur' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'logo_dark' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'supprimer_logo' => 'nullable|boolean',
        ], [
            'couleur.regex' => 'La couleur doit être au format #RRGGBB.',
            'logo.mimes' => 'Le logo doit être une image de type PNG, JPG, JPEG, SVG ou WEBP.',
            'logo.max' => 'La taille du logo ne doit pas dépasser 2 Mo.',
            'logo_dark.mimes' => 'Le logo sombre doit être une image de type PNG, JPG, JPEG, SVG ou WEBP.',
            'logo_dark.max' => 'La taille du logo sombre ne doit pas dépasser 2 Mo.',
        ], [
            'nom' => 'nom affiché',
        ]);
        $validated['couleur'] = strtoupper($validated['couleur']);

        $parametre = Parametre::actuel();

        if ($request->boolean('supprimer_logo')) {
            if ($parametre->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($parametre->logo);
            }
            if ($parametre->logo_dark) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($parametre->logo_dark);
            }
            $validated['logo'] = null;
            $validated['logo_dark'] = null;
        } else {
            if ($request->hasFile('logo')) {
                if ($parametre->logo) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($parametre->logo);
                }
                $validated['logo'] = $request->file('logo')->store('brand', 'public');
            }
            if ($request->hasFile('logo_dark')) {
                if ($parametre->logo_dark) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($parametre->logo_dark);
                }
                $validated['logo_dark'] = $request->file('logo_dark')->store('brand', 'public');
            }
        }
        unset($validated['supprimer_logo']);

        $parametre->fill($validated)->save();

        // Refresh config at runtime
        Parametre::appliquerALaConfig();

        // Logo images in the new color (made once per color, reused afterwards)
        BrandImages::generate($validated['couleur']);

        return redirect()->route('admin.apparence.edit')->with('success', 'Apparence enregistrée.');
    }
}
