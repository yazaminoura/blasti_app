@extends('admin.Layout.app')
@section('title', 'Passagers')

@section('content')
@php
    $d = $voyage->departAt();
    $canUpdate = auth()->user()->hasPermission('reservations.update') || auth()->user()->hasPermission('scanner.use');
@endphp

<x-admin.page-header :title="'Passagers · ' . $voyage->villeDepart?->ville . ' → ' . $voyage->villeArrivee?->ville"
                     :subtitle="$d->translatedFormat('l d F Y') . ' à ' . $d->format('H:i') . ' · ' . $voyage->autocar?->societe?->raison_social . ' · ' . $voyage->autocar?->matricule"
                     :back="auth()->user()->hasPermission('voyages.read') ? route('voyages.index') : route('reservation.admin.scanner', ['voyage' => $voyage->id])"
                     :backLabel="auth()->user()->hasPermission('voyages.read') ? 'Voyages' : 'Scanner'">
    @if (auth()->user()->hasPermission('scanner.use'))
        <a href="{{ route('reservation.admin.scanner', ['voyage' => $voyage->id]) }}" class="btn btn-primary"><i class="bi bi-qr-code-scan"></i> Scanner ce bus</a>
    @endif
    <button type="button" class="btn btn-soft" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
</x-admin.page-header>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6"><x-admin.stat label="Sièges vendus" :value="$stats['vendus'] . ' / ' . $stats['places']" icon="bi-ticket-perforated" /></div>
    <div class="col-md-3 col-6"><x-admin.stat label="Payés" :value="$stats['payes']" icon="bi-check2-circle" tone="success" /></div>
    <div class="col-md-3 col-6"><x-admin.stat label="À encaisser" :value="number_format($stats['a_encaisser'], 2, ',', ' ') . ' DH'" icon="bi-cash-coin" tone="warning" /></div>
    <div class="col-md-3 col-6"><x-admin.stat label="Embarqués" :value="$stats['embarques'] . ' / ' . $stats['vendus']" icon="bi-person-check" tone="info" /></div>
</div>

<x-admin.card>
    @if ($billets->isEmpty())
        <x-admin.empty icon="bi-people" title="Aucun passager" text="Aucun billet n'a encore été vendu pour ce départ." />
    @else
        <div class="table-responsive">
            <table class="table sa-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Siège</th>
                        <th>Voyageur</th>
                        <th>Trajet</th>
                        <th>Paiement</th>
                        <th>Présence</th>
                        <th class="text-end d-print-none"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($billets as $b)
                        @php $reste = $b->resteAPayer(); @endphp
                        <tr>
                            <td class="sa-num sa-strong fs-5">{{ $b->num_siege }}</td>
                            <td>
                                <div class="sa-strong">{{ $b->passager() }}</div>
                                @if ($b->passager() !== $b->user?->name)<div class="sa-sub">réservé par {{ $b->user?->name }}</div>@endif
                                <div class="sa-sub">{{ $b->user?->telephone ?: $b->user?->email }}</div>
                            </td>
                            <td>
                                <div>{{ $b->villeDepart?->ville }} → {{ $b->villeArrivee?->ville }}</div>
                                <div class="sa-sub">{{ \Carbon\Carbon::parse($b->heure_depart)->format('H:i') }} · billet #{{ $b->id }}</div>
                            </td>
                            <td>
                                @if ($b->statut === \App\Models\Reservation::EN_ATTENTE)
                                    <span class="sa-chip warning dot">Paiement carte en cours</span>
                                @elseif ($reste > 0)
                                    <span class="sa-chip warning dot">À encaisser {{ number_format($reste, 2, ',', ' ') }} DH</span>
                                @else
                                    <span class="sa-chip success dot">Payé</span>
                                @endif
                            </td>
                            <td>
                                @if ($b->isBoarded())
                                    <span class="sa-chip success dot">Embarqué {{ $b->embarque_le->format('H:i') }}</span>
                                @elseif ($b->presence_confirmee_le)
                                    <span class="sa-chip info dot">Présence confirmée</span>
                                @elseif ($b->awaitsPresence() && $b->confirmation_demandee_le)
                                    <span class="sa-chip muted dot">Confirmation demandée</span>
                                @else
                                    <span class="sa-sub">—</span>
                                @endif
                            </td>
                            <td class="text-end d-print-none text-nowrap">
                                @if ($canUpdate && $b->statut !== \App\Models\Reservation::EN_ATTENTE && ! $b->isBoarded())
                                    @if ($reste > 0)
                                        <form action="{{ route('reservation.admin.payer', $b) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-soft" title="Encaisser"><i class="bi bi-cash-coin"></i> Encaisser</button>
                                        </form>
                                    @else
                                        <form action="{{ route('reservation.admin.embarquer', $b) }}" method="POST" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-primary" title="Faire monter"><i class="bi bi-box-arrow-in-right"></i> Embarquer</button>
                                        </form>
                                    @endif
                                @endif
                                <a href="{{ route('reservation.admin.show', $b) }}" class="sa-icon-btn" title="Voir"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.card>
@endsection
