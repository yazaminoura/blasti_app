{{-- "Prix selon la date" of one voyage: the default rules, or its own (App\Support\Tarif). Param: $voyage (null on create) --}}
@php
    $propres = $voyage?->majorations;                 // null = default rules
    $mode = old('tarif_mode', is_array($propres) ? 'propre' : 'defaut');
    $lignes = old('majorations', \App\Support\Tarif::regles(is_array($propres) ? $propres : []));
    $lignes = collect($lignes)->sortByDesc(fn ($r) => (int) ($r['jours'] ?? 0))->values()->all();
    $lignes = array_slice(array_merge($lignes, array_fill(0, max(2, 4 - count($lignes)), ['jours' => '', 'type' => 'montant', 'valeur' => ''])), 0, 10);
@endphp
<x-admin.form-section title="Prix selon la date" description="Le prix monte quand le départ approche. Chaque voyage peut avoir ses propres règles." icon="bi-graph-up-arrow">
    <div class="col-12">
        <div class="d-grid gap-2">
            <label class="form-check">
                <input class="form-check-input" type="radio" name="tarif_mode" value="defaut" @checked($mode === 'defaut') data-tarif-mode>
                <span class="form-check-label">
                    <span class="fw-semibold">Tarifs par défaut</span>
                    <span class="sa-sub d-block">{{ \App\Support\Tarif::resume() }}
                        @if (auth()->user()->isSuperAdmin()) · <a href="{{ route('admin.tarifs.edit') }}" target="_blank">modifier</a>@endif</span>
                </span>
            </label>
            <label class="form-check">
                <input class="form-check-input" type="radio" name="tarif_mode" value="propre" @checked($mode === 'propre') data-tarif-mode>
                <span class="form-check-label">
                    <span class="fw-semibold">Tarifs propres à ce voyage</span>
                    <span class="sa-sub d-block">Laissez toutes les lignes vides pour un prix fixe, sans augmentation.</span>
                </span>
            </label>
        </div>

        <div class="d-grid gap-2 mt-3" data-tarif-lignes @if ($mode !== 'propre') hidden @endif>
            @error('majorations')<div class="text-danger small">{{ $message }}</div>@enderror
            @foreach ($lignes as $i => $r)
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="sa-sub">À</span>
                    <input type="number" name="majorations[{{ $i }}][jours]" value="{{ $r['jours'] }}" min="0" max="365" class="form-control" style="width: 84px;" placeholder="—" aria-label="Jours avant le départ">
                    <span class="sa-sub">jours ou moins du départ : +</span>
                    <input type="number" name="majorations[{{ $i }}][valeur]" value="{{ $r['valeur'] }}" min="0" step="0.01" class="form-control" style="width: 100px;" placeholder="—" aria-label="Augmentation">
                    <select name="majorations[{{ $i }}][type]" class="form-select" style="width: 84px;" aria-label="Type">
                        @foreach (\App\Support\Tarif::TYPES as $cle => $label)
                            <option value="{{ $cle }}" @selected(($r['type'] ?? 'montant') === $cle)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="sa-sub">0 jour = le jour du départ. Les billets déjà vendus gardent leur prix.</div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('[data-tarif-mode]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelector('[data-tarif-lignes]').hidden = radio.value !== 'propre';
            });
        });
    </script>
    @endpush
</x-admin.form-section>
