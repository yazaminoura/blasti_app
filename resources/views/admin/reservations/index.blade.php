@extends('admin.Layout.app')
@section('title', 'Réservations')

@section('content')
@php
    $canCancel = auth()->user()->hasPermission('reservations.delete');
    $f = fn ($key) => $filters[$key] ?? '';
@endphp

<x-admin.page-header title="Réservations" subtitle="Toutes les places vendues, avec recherche et filtres.">
    <a href="{{ route('admin.export.reservations', request()->query()) }}" class="btn btn-soft"><i class="bi bi-download"></i> Exporter (CSV)</a>
</x-admin.page-header>

@if ($toRefund)
    <div class="alert d-flex align-items-center gap-2 border-0 mb-3" style="background: var(--sa-danger-soft); color: var(--sa-danger);">
        <i class="bi bi-arrow-counterclockwise"></i>
        <div class="flex-grow-1">{{ $toRefund }} réservation(s) annulée(s) déjà payée(s) à rembourser.</div>
        <a href="{{ route('reservation.admin.index', ['statut' => 'a_rembourser']) }}" class="btn btn-sm btn-danger">Voir</a>
    </div>
@endif

<x-admin.filters :action="route('reservation.admin.index')">
    <div class="col-lg-3 col-md-6">
        <label class="form-label">Client</label>
        <div class="sa-search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="{{ $f('q') }}" class="form-control" placeholder="Nom, email ou téléphone">
        </div>
    </div>
    <div class="col-lg-2 col-md-3 col-6">
        <label class="form-label">Départ</label>
        <select name="ville_depart_id" class="form-select">
            <option value="">Toutes</option>
            @foreach ($villes as $ville)
                <option value="{{ $ville->id }}" @selected($f('ville_depart_id') == $ville->id)>{{ $ville->ville }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-2 col-md-3 col-6">
        <label class="form-label">Arrivée</label>
        <select name="ville_arrivee_id" class="form-select">
            <option value="">Toutes</option>
            @foreach ($villes as $ville)
                <option value="{{ $ville->id }}" @selected($f('ville_arrivee_id') == $ville->id)>{{ $ville->ville }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-2 col-md-3 col-6">
        <label class="form-label">Départ du</label>
        <input type="date" name="date_from" value="{{ $f('date_from') }}" class="form-control">
    </div>
    <div class="col-lg-2 col-md-3 col-6">
        <label class="form-label">au</label>
        <input type="date" name="date_to" value="{{ $f('date_to') }}" class="form-control">
    </div>
    <div class="col-lg-2 col-md-3 col-6">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select">
            <option value="">Tous</option>
            <option value="a_venir" @selected($f('statut') === 'a_venir')>À venir</option>
            <option value="passe" @selected($f('statut') === 'passe')>Passés</option>
            <option value="a_payer" @selected($f('statut') === 'a_payer')>À payer</option>
            <option value="payee" @selected($f('statut') === 'payee')>Payées</option>
            <option value="annulee" @selected($f('statut') === 'annulee')>Annulées</option>
            <option value="a_rembourser" @selected($f('statut') === 'a_rembourser')>À rembourser</option>
        </select>
    </div>
    <div class="col-lg-3 col-md-4 col-6">
        <label class="form-label">Mode de règlement</label>
        <select name="mode_reglement_id" class="form-select">
            <option value="">Tous</option>
            @foreach ($modes as $mode)
                <option value="{{ $mode->id }}" @selected($f('mode_reglement_id') == $mode->id)>{{ $mode->mode_reglement }}</option>
            @endforeach
        </select>
    </div>
</x-admin.filters>

<div class="row g-3 mb-3">
    <div class="col-sm-6">
        <x-admin.stat label="Réservations trouvées" :value="number_format($totals['count'], 0, ',', ' ')" icon="bi-ticket-perforated" />
    </div>
    @if (auth()->user()->hasPermission('finance.read'))
        <div class="col-sm-6">
            <x-admin.stat label="Chiffre d'affaires (après remises)" :value="number_format($totals['revenue'], 2, ',', ' ') . ' DH'" icon="bi-cash-stack" tone="success" />
        </div>
    @endif
</div>

<x-admin.card flush>
    @if ($reservations->isEmpty())
        <x-admin.empty icon="bi-inbox" title="Aucune réservation ne correspond à ces filtres" text="Essayez d'élargir les dates ou d'effacer les filtres." />
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead>
                    <tr>
                        <th>Réservation</th>
                        <th>Client</th>
                        <th>Trajet</th>
                        <th>Départ</th>
                        <th>Siège</th>
                        <th>Paiement</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $reserva)
                        @php $past = $reserva->date_depart < today()->toDateString(); @endphp
                        <tr class="{{ $past ? 'is-past' : '' }}">
                            <td class="sa-num">
                                <a href="{{ route('reservation.admin.show', $reserva->id) }}" class="sa-strong">#{{ $reserva->id }}</a>
                                <div class="sa-sub">{{ \Carbon\Carbon::parse($reserva->date_reservation)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="sa-person">
                                    <span class="sa-avatar">{{ mb_substr($reserva->user?->name ?? '?', 0, 2) }}</span>
                                    <div class="text-truncate">
                                        <div class="sa-strong">{{ $reserva->user?->name ?? '—' }}</div>
                                        <div class="sa-sub">{{ $reserva->user?->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="sa-route">{{ $reserva->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $reserva->villeArrivee?->ville }}</span></td>
                            <td class="sa-num">
                                <div>{{ \Carbon\Carbon::parse($reserva->date_depart)->format('d/m/Y') }} <span class="sa-sub">{{ \Carbon\Carbon::parse($reserva->heure_depart)->format('H:i') }}</span></div>
                                @if ($past)
                                    <span class="sa-chip muted mt-1">Passé</span>
                                @else
                                    <span class="sa-chip success dot mt-1">À venir</span>
                                @endif
                            </td>
                            <td><span class="sa-chip brand">N° {{ $reserva->num_siege }}</span></td>
                            <td>
                                @php [$badge, $tone] = $reserva->statusBadge(); @endphp
                                <span class="sa-chip {{ $tone }} dot">{{ $badge }}</span>
                                <div class="sa-sub mt-1">{{ $reserva->modeReglement?->mode_reglement ?? '—' }}</div>
                            </td>
                            <td class="text-end sa-num sa-strong text-nowrap">{{ number_format($reserva->total(), 2, ',', ' ') }} DH</td>
                            <td class="text-end">
                                <x-admin.row-actions :show="route('reservation.admin.show', $reserva->id)"
                                    :delete="$canCancel && ! $reserva->isCancelled() ? route('reservation.admin.destroy', $reserva->id) : null"
                                    deleteLabel="Annuler la réservation" deleteIcon="bi-x-circle" confirmButton="Oui, annuler"
                                    :confirm="'Annuler la réservation #' . $reserva->id . ' (siège ' . $reserva->num_siege . ') ?'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$reservations" label="réservation(s)" />
    @endif
</x-admin.card>
@endsection
