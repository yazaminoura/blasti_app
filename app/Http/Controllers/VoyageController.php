<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Voyage;
use App\Models\Ville;
use App\Http\Requests\StoreVoyageRequest;
use App\Http\Requests\UpdateVoyageRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VoyageController extends Controller
{
    /** Relations needed to render a voyage card (avoids N+1 queries). */
    private const CARD_RELATIONS = ['villeDepart', 'villeArrivee', 'typeVoyage', 'autocar.societe'];

    /** Public cards also need the stops and the sold seats (free seats depend on the segment). */
    private const PUBLIC_RELATIONS = ['villeDepart', 'villeArrivee', 'typeVoyage', 'autocar.societe', 'arrets.ville', 'reservations:id,voyage_id,num_siege,arret_depart_id,arret_arrivee_id'];

    public function listVoyages()
    {
        $villes = $this->villesWithVoyages();
        $voyages = Voyage::serving()->with(self::PUBLIC_RELATIONS)->paginate(8);

        return view('client.voyages.listevoyage', ['villes' => $villes, 'voyages' => $voyages, 'from' => null, 'to' => null, 'date' => null]);
    }

    // ================= Admin =================

    public function index(Request $request)
    {
        $filters = $request->only(Voyage::FILTERS);

        $voyages = Voyage::filter($filters)
            ->with(self::CARD_RELATIONS)
            ->withCount('reservations')
            ->orderByDesc('date_depart')
            ->orderByDesc('heure_depart')
            ->paginate(15)
            ->withQueryString();

        return view('admin.voyages.index', [
            'voyages' => $voyages,
            'filters' => $filters,
            'villes'  => Ville::orderBy('ville')->get(),
            'types'   => \App\Models\TypeVoyage::orderBy('type_voyage')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.voyages.create');
    }

    public function store(StoreVoyageRequest $request)
    {
        $formFields = $request->validated();

        if ($request->hasFile('image')) {
            $formFields['image'] = $request->file('image')->store('voyages', 'public');
        }

        $stops = $formFields['arrets'] ?? [];
        unset($formFields['arrets']);

        DB::transaction(function () use ($formFields, $stops) {
            Voyage::create($formFields)->syncArrets($stops);
        });

        return redirect()->route('voyages.index')->with('success', 'Votre voyage a été créé avec succès.');
    }

    /** Passenger list of a bus (printable): seat, name, trip, payment, boarded — for the controller / driver. */
    public function passagers(Voyage $voyage)
    {
        $voyage->load(['autocar.societe', 'arrets.ville', 'villeDepart', 'villeArrivee']);
        $billets = $voyage->reservations()
            ->with(['user:id,name,telephone,email', 'villeDepart', 'villeArrivee', 'modeReglement'])
            ->orderBy('num_siege')->get();

        $stats = [
            'places' => (int) $voyage->autocar?->nbr_siege,
            'vendus' => $billets->count(),
            'payes' => $billets->filter(fn ($b) => $b->resteAPayer() <= 0)->count(),
            'a_encaisser' => $billets->sum(fn ($b) => $b->resteAPayer()),
            'embarques' => $billets->filter->isBoarded()->count(),
        ];

        return view('admin.voyages.passagers', compact('voyage', 'billets', 'stats'));
    }

    public function edit(Voyage $voyage)
    {
        return view('admin.voyages.edit', compact('voyage'));
    }

    public function update(UpdateVoyageRequest $request, Voyage $voyage)
    {
        $formFields = $request->validated();

        if ($request->hasFile('image')) {
            if ($voyage->image) {
                Storage::disk('public')->delete($voyage->image);
            }
            $formFields['image'] = $request->file('image')->store('voyages', 'public');
        }

        $stops = $formFields['arrets'] ?? [];
        unset($formFields['arrets']);

        // Reservations keep a copy of their segment (tickets, exports, filters): keep them in sync
        // with their boarding / drop-off stops. The price already paid is not touched.
        try {
            $synced = DB::transaction(function () use ($voyage, $formFields, $stops) {
                $voyage->update($formFields);
                $voyage->syncArrets($stops);

                $reservations = $voyage->reservations()->with(['arretDepart', 'arretArrivee'])->get();
                foreach ($reservations as $reservation) {
                    $reservation->update([
                        'date_depart' => $reservation->arretDepart?->passage_at->toDateString() ?? $voyage->date_depart,
                        'heure_depart' => $reservation->arretDepart?->passage_at->format('H:i:s') ?? $voyage->heure_depart,
                        'date_arrivee' => $reservation->arretArrivee?->passage_at->toDateString() ?? $voyage->date_arrivee,
                        'heure_arrivee' => $reservation->arretArrivee?->passage_at->format('H:i:s') ?? $voyage->heure_arrivee,
                        'ville_depart_id' => $reservation->arretDepart?->ville_id ?? $voyage->ville_depart_id,
                        'ville_arrivee_id' => $reservation->arretArrivee?->ville_id ?? $voyage->ville_arrivee_id,
                        'autocar_id' => $voyage->autocar_id,
                        'type_voyage_id' => $voyage->type_voyage_id,
                    ]);
                }

                return $reservations->count();
            });
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['arrets' => $e->getMessage()]);
        }

        $message = 'Votre voyage a été modifié avec succès.';
        if ($synced) {
            $message .= " Les {$synced} billet(s) déjà vendu(s) ont été mis à jour.";
        }

        return redirect()->route('voyages.index')->with('success', $message);
    }

    public function destroy(Voyage $voyage)
    {
        if ($voyage->reservations()->exists()) {
            return redirect()->route('voyages.index')->with('error', 'Impossible de supprimer ce voyage : il contient des réservations.');
        }

        try {
            $voyage->delete();
        } catch (QueryException $e) {
            return redirect()->route('voyages.index')->with('error', "Impossible de supprimer ce voyage car il est lié à d'autres données.");
        }

        if ($voyage->image) {
            Storage::disk('public')->delete($voyage->image);
        }

        return redirect()->route('voyages.index')->with('success', 'Votre voyage a été supprimé avec succès.');
    }

    // ================= Client (AJAX filters) =================

    /**
     * Voyage list filters (AJAX): every criterion is combined, so the city/date search, the company
     * name and the sidebar checkboxes no longer reset each other.
     */
    public function filter(Request $request)
    {
        $from = $request->integer('ville_depart') ?: null;
        $to = $request->integer('ville_arrivee') ?: null;
        $date = $request->filled('date_depart') ? $request->date('date_depart') : null;
        $ids = fn (string $key) => array_filter(array_map('intval', (array) $request->input($key, [])));

        $search = fn ($day) => Voyage::serving($from, $to, $day)->with(self::PUBLIC_RELATIONS)
            ->when($ids('type_voyages'), fn ($q, $types) => $q->whereIn('type_voyage_id', $types))
            ->when($ids('options'), fn ($q, $options) => $q->whereHas('autocar.options', fn ($o) => $o->whereIn('options.id', $options)))
            ->when($ids('equipements'), fn ($q, $equipements) => $q->whereHas('autocar.equipements', fn ($e) => $e->whereIn('equipements.id', $equipements)))
            ->when(trim((string) $request->input('societe')), fn ($q, $term) => $q->whereHas('autocar.societe', fn ($s) => $s->where('raison_social', 'like', '%' . $term . '%')))
            ->take(60)->get();

        $voyages = $search($date);

        // nothing that day: the next departures on the same trip, with a notice
        $autresDates = false;
        if ($date && $voyages->isEmpty()) {
            $voyages = $search(null);
            $autresDates = $voyages->isNotEmpty();
        }

        return response()->json([
            'voyages' => view('client.voyages.partials.list-voyage', [
                'voyages' => $voyages, 'from' => $from, 'to' => $to, 'autresDates' => $autresDates, 'date' => $date?->toDateString(),
            ])->render(),
        ]);
    }

    /** Old endpoints, kept for existing links: same combined filter. */
    public function filter_sidebar(Request $request)
    {
        return $this->filter($request);
    }

    public function search(Request $request)
    {
        if ($request->filled('societes') && ! $request->filled('societe')) {
            $request->merge(['societe' => (string) $request->input('societes')]);
        }

        return $this->filter($request);
    }

    public function clientIndex(Request $request)
    {
        $from = $request->integer('ville_depart') ?: null;
        $to = $request->integer('ville_arrivee') ?: null;
        $date = $request->filled('date_depart') ? $request->date('date_depart') : null;

        $voyages = Voyage::serving($from, $to, $date)->with(self::PUBLIC_RELATIONS)->paginate(10)->withQueryString();

        // nothing that day: show the next departures on the same trip instead of an empty page
        $autresDates = false;
        if ($date && $voyages->isEmpty()) {
            $voyages = Voyage::serving($from, $to)->with(self::PUBLIC_RELATIONS)->paginate(10)->withQueryString();
            $autresDates = $voyages->isNotEmpty();
        }

        return view('client.voyages.listevoyage', [
            'voyages' => $voyages,
            'villes'  => $this->villesWithVoyages(),
            'from' => $from, 'to' => $to, 'date' => $date?->toDateString(), 'autresDates' => $autresDates,
        ]);
    }

    public function detail(Request $request)
    {
        // The old detail page was the template's flight demo: the booking page is the real voyage page
        $voyage = Voyage::findOrFail($request->integer('id'));

        return redirect()->route('client.reservations.show', $voyage);
    }

    private function villesWithVoyages()
    {
        return Ville::whereHas('arrets')->orderBy('ville')->get();
    }

    private function renderList($voyages, $from = null, $to = null)
    {
        return response()->json([
            'voyages' => view('client.voyages.partials.list-voyage', compact('voyages', 'from', 'to'))->render(),
        ]);
    }
}
