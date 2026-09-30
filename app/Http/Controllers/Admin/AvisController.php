<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\Societe;
use Illuminate\Http\Request;

/** Reviews moderation: hide an insulting or fake review, delete it. A company account only sees its own. */
class AvisController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Avis::with(['user:id,name', 'societe:id,raison_social', 'reservation.villeDepart', 'reservation.villeArrivee'])
            ->when($user->societe_id, fn ($q, $id) => $q->where('societe_id', $id))
            ->when($request->filled('societe_id'), fn ($q) => $q->where('societe_id', $request->integer('societe_id')))
            ->when($request->filled('note'), fn ($q) => $q->where('note', $request->integer('note')))
            ->latest();

        return view('admin.avis.index', [
            'avis' => $query->paginate(20)->withQueryString(),
            'societes' => $user->societe_id ? collect() : Societe::orderBy('raison_social')->get(['id', 'raison_social']),
            'moyenne' => round((float) (clone $query)->reorder()->where('publie', true)->avg('note'), 1),
            'filters' => $request->only(['societe_id', 'note']),
        ]);
    }

    public function publier(Avis $avis)
    {
        $this->own($avis);
        $avis->update(['publie' => ! $avis->publie]);

        return back()->with('success', $avis->publie ? 'Avis de nouveau visible.' : 'Avis masqué : il ne compte plus dans la note.');
    }

    public function destroy(Avis $avis)
    {
        $this->own($avis);
        $avis->delete();

        return back()->with('success', 'Avis supprimé.');
    }

    private function own(Avis $avis): void
    {
        $societe = auth()->user()->societe_id;
        abort_if($societe && (int) $avis->societe_id !== (int) $societe, 403);
    }
}
