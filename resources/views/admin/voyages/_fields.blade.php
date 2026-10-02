{{-- Voyage fields, shared by create and edit. $voyage is null on create. --}}
@php
    $villes = \App\Models\Ville::orderBy('ville')->pluck('ville', 'id');
    $autocars = \App\Models\Autocar::with('societe')->get()
        ->mapWithKeys(fn ($a) => [$a->id => $a->matricule . ' — ' . ($a->societe?->raison_social ?? 'sans société') . ' (' . $a->nbr_siege . ' places)']);
    $types = \App\Models\TypeVoyage::pluck('type_voyage', 'id');
    $time = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('H:i') : null;
@endphp

<x-admin.form-section title="Trajet" description="Villes de départ et d'arrivée, et le type de voyage." icon="bi-signpost-split">
    <x-admin.field type="select" name="ville_depart_id" label="Ville de départ" col="col-md-6" required
                   :options="$villes" empty="Choisir une ville" :value="$voyage?->ville_depart_id" />
    <x-admin.field type="select" name="ville_arrivee_id" label="Ville d'arrivée" col="col-md-6" required
                   :options="$villes" empty="Choisir une ville" :value="$voyage?->ville_arrivee_id" />
    <x-admin.field type="select" name="type_voyage_id" label="Type de voyage" col="col-md-6" required
                   :options="$types" empty="Choisir un type" :value="$voyage?->type_voyage_id" />
</x-admin.form-section>

<x-admin.form-section title="Horaires" description="L'arrivée doit être le même jour ou après le départ." icon="bi-clock">
    <x-admin.field type="date" name="date_depart" label="Date de départ" col="col-md-6" required :value="$voyage?->date_depart" />
    <x-admin.field type="time" name="heure_depart" label="Heure de départ" col="col-md-6" required :value="$time($voyage?->heure_depart)" />
    <x-admin.field type="date" name="date_arrivee" label="Date d'arrivée" col="col-md-6" required :value="$voyage?->date_arrivee" />
    <x-admin.field type="time" name="heure_arrivee" label="Heure d'arrivée" col="col-md-6" required :value="$time($voyage?->heure_arrivee)" />
</x-admin.form-section>

@php
    // intermediate stops: old input after a validation error, else the voyage's stops (first and last excluded)
    $arretsInit = old('arrets', $voyage
        ? $voyage->arrets()->get()->slice(1, -1)->map(fn ($a) => ['ville_id' => $a->ville_id, 'heure' => $a->passage_at->format('H:i'), 'prix' => $a->prix])->values()->all()
        : []);
@endphp
<x-admin.form-section title="Arrêts intermédiaires" description="Les villes où le bus s'arrête entre le départ et l'arrivée, dans l'ordre. Un client peut monter et descendre à chaque arrêt : une place libérée à un arrêt est revendue pour la suite du trajet." icon="bi-signpost-2">
    <div class="col-12">
        @error('arrets')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        <div id="arrets-list" class="d-grid gap-2">
            @foreach ($arretsInit as $i => $arret)
                <div class="row g-2 align-items-end arret-row">
                    <div class="col-md-5">
                        <label class="form-label">Ville</label>
                        <select name="arrets[{{ $i }}][ville_id]" class="form-select @error("arrets.$i.ville_id") is-invalid @enderror" required>
                            <option value="">Choisir une ville</option>
                            @foreach ($villes as $id => $nom)
                                <option value="{{ $id }}" @selected((string) ($arret['ville_id'] ?? '') === (string) $id)>{{ $nom }}</option>
                            @endforeach
                        </select>
                        @error("arrets.$i.ville_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Heure de passage</label>
                        <input type="time" name="arrets[{{ $i }}][heure]" value="{{ $arret['heure'] ?? '' }}" class="form-control @error("arrets.$i.heure") is-invalid @enderror" required>
                        @error("arrets.$i.heure")<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Prix depuis le départ</label>
                        <div class="input-group has-validation">
                            <input type="number" name="arrets[{{ $i }}][prix]" value="{{ $arret['prix'] ?? '' }}" min="0" step="0.01" class="form-control @error("arrets.$i.prix") is-invalid @enderror" required>
                            <span class="input-group-text">DH</span>
                            @error("arrets.$i.prix")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger-soft w-100 arret-remove" title="Retirer l'arrêt"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-soft mt-2" id="arret-add"><i class="bi bi-plus-lg"></i> Ajouter un arrêt</button>
        <div class="form-text">Exemple Fès → Rabat : Imouzzer 08:40 · 25 DH, Meknès 10:10 · 50 DH. Le prix d'un trajet partiel = prix de l'arrêt de descente − prix de l'arrêt de montée.</div>
    </div>
    <template id="arret-template">
        <div class="row g-2 align-items-end arret-row">
            <div class="col-md-5">
                <label class="form-label">Ville</label>
                <select data-name="ville_id" class="form-select" required>
                    <option value="">Choisir une ville</option>
                    @foreach ($villes as $id => $nom)
                        <option value="{{ $id }}">{{ $nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Heure de passage</label>
                <input type="time" data-name="heure" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Prix depuis le départ</label>
                <div class="input-group"><input type="number" data-name="prix" min="0" step="0.01" class="form-control" required><span class="input-group-text">DH</span></div>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger-soft w-100 arret-remove" title="Retirer l'arrêt"><i class="bi bi-trash"></i></button>
            </div>
        </div>
    </template>
    <script>
        (function () {
            const list = document.getElementById('arrets-list');
            // field names follow the row order, so the stops are saved in the order shown
            const renumber = () => list.querySelectorAll('.arret-row').forEach((row, i) =>
                row.querySelectorAll('[data-name], [name^="arrets["]').forEach(el => {
                    const key = el.dataset.name || el.name.replace(/^arrets\[\d+\]\[(\w+)\]$/, '$1');
                    el.dataset.name = key;
                    el.name = `arrets[${i}][${key}]`;
                }));
            document.getElementById('arret-add').addEventListener('click', () => {
                list.appendChild(document.getElementById('arret-template').content.cloneNode(true));
                renumber();
                list.lastElementChild.querySelector('select').focus();
            });
            list.addEventListener('click', e => {
                const btn = e.target.closest('.arret-remove');
                if (btn) { btn.closest('.arret-row').remove(); renumber(); }
            });
        })();
    </script>
</x-admin.form-section>

@php
    $chauffeurs = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['chauffeur', 'compagnie-chauffeur']))
        ->orWhere('isadmin', 1)
        ->orderBy('name')
        ->pluck('name', 'id');
@endphp

<x-admin.form-section title="Autocar, chauffeur et prix" description="Le nombre de places vendues dépend de la capacité de l'autocar." icon="bi-bus-front">
    <x-admin.field type="select" name="autocar_id" label="Autocar" col="col-md-6" required
                   :options="$autocars" empty="Choisir un autocar" :value="$voyage?->autocar_id" />
    <x-admin.field type="select" name="chauffeur_id" label="Chauffeur assigné" col="col-md-6"
                   :options="$chauffeurs" empty="Choisir un chauffeur (optionnel)" :value="$voyage?->chauffeur_id" />
    <x-admin.field type="number" name="prix" label="Prix du trajet complet" col="col-md-6" required suffix="DH"
                   min="0" step="0.01" placeholder="0,00" :value="$voyage?->prix" />
</x-admin.form-section>

@include('admin.voyages._tarifs')

<x-admin.form-section title="Image" description="Affichée sur la fiche du voyage, côté site." icon="bi-image">
    <x-admin.upload name="image" label="Image du voyage" :current="$voyage?->image" hint="JPG, PNG ou WEBP, 2 Mo maximum." />
</x-admin.form-section>
