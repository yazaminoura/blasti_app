@extends('admin.Layout.app')
@section('title', 'Tarifs par défaut')

@section('content')
@php
    // existing rules, then empty lines to add more (up to 10)
    $lignes = old('regles', $regles);
    $lignes = array_merge(array_values($lignes), array_fill(0, max(2, 4 - count($lignes)), ['jours' => '', 'type' => 'montant', 'valeur' => '']));
    $lignes = array_slice($lignes, 0, 10);
@endphp

<x-admin.page-header title="Tarifs par défaut"
    subtitle="Le prix monte quand le départ approche. Ces règles valent pour les voyages qui n'ont pas leurs propres tarifs (fiche du voyage > Prix selon la date). Les billets déjà vendus ne changent pas." />

<div class="row g-4">
    <div class="col-xl-7">
        <x-admin.form :action="route('admin.tarifs.update')" method="PUT" hint="Laissez une ligne vide pour la supprimer.">
            <x-admin.form-section title="Augmentations" description="« À J jours ou moins du départ : + montant ». C'est la ligne la plus proche du départ qui compte." icon="bi-graph-up-arrow">
                <div class="col-12">
                    @if ($errors->any())
                        <div class="text-danger small mb-2">{{ $errors->first() }}</div>
                    @endif
                    <div class="d-grid gap-2" id="regles">
                        @foreach ($lignes as $i => $r)
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="sa-sub">À</span>
                                <input type="number" name="regles[{{ $i }}][jours]" value="{{ $r['jours'] }}" min="0" max="365" class="form-control" style="width: 90px;" placeholder="—" data-jours aria-label="Jours avant le départ">
                                <span class="sa-sub">jours ou moins du départ :</span>
                                <span class="sa-sub">+</span>
                                <input type="number" name="regles[{{ $i }}][valeur]" value="{{ $r['valeur'] }}" min="0" step="0.01" class="form-control" style="width: 110px;" placeholder="—" data-valeur aria-label="Augmentation">
                                <select name="regles[{{ $i }}][type]" class="form-select" style="width: 90px;" data-type aria-label="Type">
                                    @foreach (\App\Support\Tarif::TYPES as $cle => $label)
                                        <option value="{{ $cle }}" @selected(($r['type'] ?? 'montant') === $cle)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="sa-sub mt-2">0 jour = le jour du départ. Exemple : « 7 jours : +10 DH » et « 4 jours : +20 DH ».</div>
                </div>
            </x-admin.form-section>
        </x-admin.form>
    </div>

    <div class="col-xl-5">
        <x-admin.card title="Aperçu" subtitle="Prix d'un billet selon le jour où il est acheté." icon="bi-eye">
            <div class="d-flex align-items-center gap-2 mb-3">
                <label for="base" class="sa-sub">Prix normal du voyage</label>
                <input type="number" id="base" value="100" min="1" class="form-control" style="width: 110px;">
                <span class="sa-sub">DH</span>
            </div>
            <table class="table sa-table align-middle mb-0">
                <thead><tr><th>Acheté</th><th class="text-end">Prix</th></tr></thead>
                <tbody id="apercu"></tbody>
            </table>
        </x-admin.card>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var fmt = function (n) { return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DH'; };
        function regles() {
            return Array.prototype.slice.call(document.querySelectorAll('#regles > div')).map(function (l) {
                return { jours: parseInt(l.querySelector('[data-jours]').value, 10), valeur: parseFloat(l.querySelector('[data-valeur]').value), type: l.querySelector('[data-type]').value };
            }).filter(function (r) { return !isNaN(r.jours) && r.valeur > 0; }).sort(function (a, b) { return a.jours - b.jours; });
        }
        function prix(base, jours, rs) {
            var r = rs.find(function (x) { return jours <= x.jours; });
            return r ? base + (r.type === 'pourcentage' ? base * r.valeur / 100 : r.valeur) : base;
        }
        function maj() {
            var base = parseFloat(document.getElementById('base').value) || 0;
            var rs = regles();
            var paliers = [30].concat(rs.map(function (r) { return r.jours + 1; }), rs.map(function (r) { return r.jours; }), [0])
                .filter(function (j, i, a) { return j >= 0 && a.indexOf(j) === i; }).sort(function (a, b) { return b - a; });
            document.getElementById('apercu').innerHTML = paliers.map(function (j) {
                var p = prix(base, j, rs);
                var quand = j === 0 ? 'Le jour du départ' : (j === 1 ? 'La veille' : j + ' jours avant');
                return '<tr><td>' + quand + '</td><td class="text-end sa-num ' + (p > base ? 'sa-strong' : '') + '">' + fmt(p) + '</td></tr>';
            }).join('');
        }
        document.addEventListener('input', maj);
        document.addEventListener('change', maj);
        maj();
    })();
</script>
@endpush
@endsection
