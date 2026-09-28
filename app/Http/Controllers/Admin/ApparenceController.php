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
        ], [
            'couleur.regex' => 'La couleur doit être au format #RRGGBB.',
        ], [
            'nom' => 'nom affiché',
        ]);
        $validated['couleur'] = strtoupper($validated['couleur']);

        $parametre = Parametre::actuel();
        $parametre->fill($validated)->save();

        // Logo images in the new color (made once per color, reused afterwards)
        BrandImages::generate($validated['couleur']);

        return redirect()->route('admin.apparence.edit')->with('success', 'Apparence enregistrée.');
    }
}
