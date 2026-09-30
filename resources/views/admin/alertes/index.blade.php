@extends('admin.Layout.app')
@section('title', 'Alertes contrôle')

@section('content')
<x-admin.page-header title="Alertes contrôle"
    subtitle="Billets scannés « À encaisser » à la porte du bus, puis jamais payés ni montés. Le voyageur a pu renoncer… ou payer sans que ce soit enregistré.">
    <nav class="sa-tabs">
        @foreach ([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $j => $label)
            <a href="{{ route('admin.alertes', ['jours' => $j]) }}" class="sa-tab {{ $jours === $j ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
</x-admin.page-header>

<div class="row g-4">
    <div class="col-xl-4">
        <x-admin.card title="Par contrôleur" subtitle="Un nombre bien plus haut que les autres mérite une vérification." icon="bi-person-badge" flush>
            @forelse ($parControleur as $nom => $total)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                    <span class="sa-strong">{{ $nom }}</span>
                    <span class="sa-chip {{ $total >= 3 ? 'danger' : 'warning' }}">{{ $total }}</span>
                </div>
            @empty
                <div class="p-3 sa-sub">Aucune alerte sur la période.</div>
            @endforelse
        </x-admin.card>
    </div>

    <div class="col-xl-8">
        <x-admin.card flush>
            @if ($alertes->isEmpty())
                <x-admin.empty icon="bi-shield-check" title="Aucune alerte" text="Tous les billets scannés « À encaisser » ont été payés ou le voyageur est monté." />
            @else
                <div class="sa-table-wrap">
                    <table class="table sa-table align-middle">
                        <thead><tr><th>Billet</th><th>Voyageur</th><th>Trajet · départ</th><th>Scanné par</th><th class="text-end">Montant</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($alertes as $r)
                                @php $scan = $r->scans->first(); @endphp
                                <tr>
                                    <td class="sa-num sa-strong">#{{ $r->id }}<div class="sa-sub">Siège {{ $r->num_siege }}</div></td>
                                    <td>
                                        <div class="sa-strong">{{ $r->passager() }}</div>
                                        <div class="sa-sub">{{ $r->user?->telephone ?: $r->user?->email }}</div>
                                    </td>
                                    <td>
                                        <span class="sa-route">{{ $r->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $r->villeArrivee?->ville }}</span>
                                        <div class="sa-sub">{{ \Carbon\Carbon::parse($r->date_depart)->format('d/m/Y') }} {{ \Carbon\Carbon::parse($r->heure_depart)->format('H:i') }} · {{ $r->autocar?->societe?->raison_social }}</div>
                                    </td>
                                    <td>
                                        <div class="sa-strong">{{ $scan?->user?->name ?? '—' }}</div>
                                        <div class="sa-sub">{{ $scan?->created_at?->format('d/m H:i') }}{{ $r->scans->count() > 1 ? ' · ' . $r->scans->count() . ' scans' : '' }}</div>
                                    </td>
                                    <td class="text-end sa-num text-nowrap">{{ number_format($r->total(), 2, ',', ' ') }} DH</td>
                                    <td class="text-end"><x-admin.row-actions :show="route('reservation.admin.show', $r->id)" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-admin.table-footer :paginator="$alertes" label="alerte(s)" />
            @endif
        </x-admin.card>
    </div>
</div>
@endsection
