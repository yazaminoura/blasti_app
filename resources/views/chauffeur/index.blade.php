@extends('admin.Layout.app')
@section('title', 'Espace Chauffeur')

@section('content')
@php
    $fStatut = $onglet;
@endphp

<x-admin.page-header title="Espace Chauffeur" subtitle="Vos départs programmés, les horaires d'arrivée, les arrêts et le nombre de passagers prévus.">
    <div class="btn-group">
        <a href="{{ route('chauffeur.index', ['statut' => 'a_venir']) }}" class="btn {{ $fStatut !== 'passes' ? 'btn-primary' : 'btn-soft' }}">
            <i class="bi bi-calendar-event"></i> À venir & Aujourd'hui
        </a>
        <a href="{{ route('chauffeur.index', ['statut' => 'passes']) }}" class="btn {{ $fStatut === 'passes' ? 'btn-primary' : 'btn-soft' }}">
            <i class="bi bi-clock-history"></i> Départs passés
        </a>
    </div>
</x-admin.page-header>

<x-admin.card>
    @if ($voyages->isEmpty())
        <x-admin.empty icon="bi-bus-front" title="Aucun départ assigné" text="Vos prochains voyages planifiés apparaîtront ici." />
    @else
        <div class="table-responsive">
            <table class="table sa-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Trajet</th>
                        <th>Départ</th>
                        <th class="sa-hide-md">Arrivée</th>
                        <th>Autocar</th>
                        <th>Passagers</th>
                        <th>Arrêts</th>
                        <th class="text-end">Feuille de route</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($voyages as $v)
                        @php
                            $rate = $v->occupancyRate();
                            $isToday = \Carbon\Carbon::parse($v->date_depart)->isToday();
                            $past = $v->isPast();
                        @endphp
                        <tr class="{{ $past ? 'is-past' : '' }}">
                            <td>
                                <div class="sa-route">{{ $v->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $v->villeArrivee?->ville }}</div>
                                <div class="d-flex gap-1 mt-1">
                                    @if ($isToday)
                                        <span class="sa-chip brand dot">Aujourd'hui</span>
                                    @elseif ($past)
                                        <span class="sa-chip muted">Passé</span>
                                    @else
                                        <span class="sa-chip success dot">À venir</span>
                                    @endif
                                    @if ($v->estEnRetard())
                                        <span class="sa-chip warning"><i class="bi bi-clock-history"></i> +{{ $v->retard_minutes }} min</span>
                                    @endif
                                </div>
                            </td>
                            <td class="sa-num">
                                <div class="sa-strong">{{ \Carbon\Carbon::parse($v->date_depart)->format('d/m/Y') }}</div>
                                <div class="sa-sub">
                                    {{ \Carbon\Carbon::parse($v->heure_depart)->format('H:i') }}
                                    @if ($v->estEnRetard())
                                        <span class="text-danger">({{ $v->heureDepartEstimee() }})</span>
                                    @endif
                                </div>
                            </td>
                            <td class="sa-num sa-hide-md">
                                <div>{{ \Carbon\Carbon::parse($v->date_arrivee)->format('d/m/Y') }}</div>
                                <div class="sa-sub">
                                    {{ \Carbon\Carbon::parse($v->heure_arrivee)->format('H:i') }}
                                    @if ($v->estEnRetard())
                                        <span class="text-danger">({{ $v->heureArriveeEstimee() }})</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="sa-mono">{{ $v->autocar?->matricule }}</div>
                                <div class="sa-sub">{{ $v->autocar?->societe?->raison_social }}</div>
                            </td>
                            <td>
                                <div class="sa-occupancy">
                                    <div class="progress">
                                        <div class="progress-bar {{ $rate >= 80 ? 'bg-success' : ($rate >= 40 ? '' : 'bg-warning') }}" style="width: {{ $rate }}%"></div>
                                    </div>
                                    <span>{{ $v->reservations_count }} / {{ $v->autocar?->nbr_siege ?? 0 }}</span>
                                </div>
                            </td>
                            <td class="sa-sub">
                                {{ $v->arrets->count() }} arrêt(s)
                            </td>
                            <td class="text-end">
                                <a href="{{ route('chauffeur.show', $v) }}" class="btn btn-sm btn-soft">
                                    <i class="bi bi-eye"></i> Feuille de route
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$voyages" label="départ(s)" />
    @endif
</x-admin.card>
@endsection
