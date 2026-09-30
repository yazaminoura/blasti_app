@extends('admin.Layout.app')
@section('title', 'Statistiques')

@section('content')
@php
    $dh = fn ($n) => number_format($n, 2, ',', ' ') . ' DH';
    $maxCa = max(1, $mois->max('ca'));
@endphp

<x-admin.page-header title="Statistiques des ventes" :subtitle="'Du ' . $du->format('d/m/Y') . ' au ' . $au->format('d/m/Y')" />

<x-admin.filters :action="route('admin.statistiques')">
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Du</label>
        <input type="date" name="du" class="form-control" value="{{ $du->toDateString() }}">
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Au</label>
        <input type="date" name="au" class="form-control" value="{{ $au->toDateString() }}">
    </div>
    <div class="col-lg-4 col-md-12 d-flex align-items-end gap-2 flex-wrap">
        @foreach (['Ce mois' => [now()->startOfMonth(), now()], 'Mois dernier' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()], 'Cette année' => [now()->startOfYear(), now()]] as $label => [$a, $b])
            <a href="{{ route('admin.statistiques', ['du' => $a->toDateString(), 'au' => $b->toDateString()]) }}" class="btn btn-soft btn-sm">{{ $label }}</a>
        @endforeach
    </div>
</x-admin.filters>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><x-admin.stat label="Chiffre d'affaires réservé" :value="$dh($kpis['ca'])" icon="bi-graph-up-arrow">{{ $kpis['billets'] }} billet(s) · {{ $kpis['commandes'] }} commande(s)</x-admin.stat></div>
    <div class="col-xl-3 col-md-6"><x-admin.stat label="Encaissé" :value="$dh($kpis['encaisse'])" icon="bi-cash-stack" tone="success">paiements reçus sur la période</x-admin.stat></div>
    <div class="col-xl-3 col-md-6"><x-admin.stat label="Reste à encaisser" :value="$dh($kpis['a_encaisser'])" icon="bi-hourglass-split" tone="warning">à l'embarquement / en agence</x-admin.stat></div>
    <div class="col-xl-3 col-md-6"><x-admin.stat label="Commission ({{ rtrim(rtrim(number_format($taux, 2, ',', ''), '0'), ',') }} %)" :value="$dh($kpis['commission'])" icon="bi-percent" tone="info">sur le chiffre d'affaires réservé</x-admin.stat></div>
    <div class="col-xl-3 col-md-6"><x-admin.stat label="Panier moyen" :value="$dh($kpis['panier'])" icon="bi-basket" /></div>
    <div class="col-xl-3 col-md-6"><x-admin.stat label="Remises accordées" :value="$dh($kpis['remises'])" icon="bi-ticket-perforated">codes promo et allers-retours</x-admin.stat></div>
    <div class="col-xl-3 col-md-6"><x-admin.stat label="Annulations" :value="$kpis['annulations']" icon="bi-x-circle" tone="danger">{{ $kpis['taux_annulation'] }} % des billets</x-admin.stat></div>
</div>

<x-admin.card title="12 derniers mois" class="mb-3">
    <div class="sa-bars">
        @foreach ($mois as $k => $m)
            <div class="sa-bar" title="{{ \Carbon\Carbon::createFromFormat('Y-m', $k)->translatedFormat('F Y') }} : {{ $m['billets'] }} billet(s), {{ $dh($m['ca']) }}">
                <div class="sa-bar-value">{{ $m['ca'] >= 1000 ? number_format($m['ca'] / 1000, 1, ',', '') . 'k' : number_format($m['ca'], 0) }}</div>
                <div class="sa-bar-fill" style="height: {{ max(2, round($m['ca'] * 100 / $maxCa)) }}%;"></div>
                <div class="sa-bar-label">{{ \Carbon\Carbon::createFromFormat('Y-m', $k)->translatedFormat('M') }}</div>
            </div>
        @endforeach
    </div>
</x-admin.card>

<div class="row g-3">
    <div class="col-xl-7">
        <x-admin.card title="Par compagnie">
            <div class="table-responsive">
                <table class="table sa-table align-middle mb-0">
                    <thead><tr><th>Compagnie</th><th class="text-end">Billets</th><th class="text-end">Chiffre d'affaires</th><th class="text-end">À encaisser</th><th class="text-end">Commission</th></tr></thead>
                    <tbody>
                        @forelse ($parSociete as $nom => $l)
                            <tr>
                                <td class="sa-strong">{{ $nom }}</td>
                                <td class="text-end sa-num">{{ $l['billets'] }}</td>
                                <td class="text-end sa-num sa-strong">{{ $dh($l['ca']) }}</td>
                                <td class="text-end sa-num">{{ $dh($l['a_encaisser']) }}</td>
                                <td class="text-end sa-num">{{ $dh($l['commission']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center sa-sub py-4">Aucune vente sur cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
    <div class="col-xl-5">
        <x-admin.card title="Trajets les plus vendus">
            <div class="table-responsive">
                <table class="table sa-table align-middle mb-0">
                    <thead><tr><th>Trajet</th><th class="text-end">Billets</th><th class="text-end">CA</th></tr></thead>
                    <tbody>
                        @forelse ($parTrajet as $trajet => $l)
                            <tr><td>{{ $trajet }}</td><td class="text-end sa-num">{{ $l['billets'] }}</td><td class="text-end sa-num">{{ $dh($l['ca']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center sa-sub py-4">Aucune vente sur cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </div>
</div>
@endsection
