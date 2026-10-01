<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Promo codes: a % or an amount off the whole order, with dates, minimum order and number of uses. */
class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::withSum('reservations', 'remise')->latest()->paginate(20);

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create()
    {
        return view('admin.promotions.create', ['promotion' => new Promotion(['type' => 'pourcentage', 'actif' => true, 'max_par_client' => 1])]);
    }

    public function store(Request $request)
    {
        Promotion::create($this->validated($request));

        return redirect()->route('promotions.index')->with('success', 'Code promo créé.');
    }

    public function edit(Promotion $promotion)
    {
        return view('admin.promotions.edit', compact('promotion'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $promotion->update($this->validated($request, $promotion));

        return redirect()->route('promotions.index')->with('success', 'Code promo enregistré.');
    }

    public function destroy(Promotion $promotion)
    {
        // used codes stay in the history of the tickets: they are only switched off
        if ($promotion->reservations()->withoutGlobalScopes()->exists()) {
            $promotion->update(['actif' => false]);

            return back()->with('success', "Le code {$promotion->code} a déjà servi : il est désactivé au lieu d'être supprimé.");
        }
        $promotion->delete();

        return back()->with('success', 'Code promo supprimé.');
    }

    private function validated(Request $request, ?Promotion $promotion = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('promotions', 'code')->ignore($promotion?->id)],
            'type' => ['required', Rule::in(['pourcentage', 'montant'])],
            'valeur' => ['required', 'numeric', 'min:0.01', $request->input('type') === 'pourcentage' ? 'max:100' : 'max:100000'],
            'min_montant' => ['nullable', 'numeric', 'min:0'],
            'debut' => ['nullable', 'date'],
            'fin' => ['nullable', 'date', 'after_or_equal:debut'],
            'max_utilisations' => ['nullable', 'integer', 'min:1'],
            'max_par_client' => ['nullable', 'integer', 'min:1'],
        ], [
            'code.regex' => 'Le code ne peut contenir que des lettres, des chiffres, - et _ (sans espace).',
            'valeur.max' => 'Une réduction en pourcentage ne peut pas dépasser 100 %.',
        ]);
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
