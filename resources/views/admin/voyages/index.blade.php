@extends('admin.Layout.app')
@section('title', 'Voyages')

@section('content')
@php
    $user = auth()->user();
    $canUpdate = $user->hasPermission('voyages.update');
    $canDelete = $user->hasPermission('voyages.delete');
    $f = fn ($key) => $filters[$key] ?? '';
@endphp

<x-admin.page-header title="Voyages" subtitle="Les départs programmés, leur remplissage et leur prix.">
    <a href="{{ route('admin.export.voyages') }}" class="btn btn-soft"><i class="bi bi-download"></i> Exporter</a>
    @if ($user->hasPermission('voyages.create'))
        <a href="{{ route('voyages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouveau voyage</a>
    @endif
</x-admin.page-header>

<x-admin.filters :action="route('voyages.index')">
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Départ</label>
        <select name="ville_depart_id" class="form-select">
            <option value="">Toutes</option>
            @foreach ($villes as $ville)
                <option value="{{ $ville->id }}" @selected($f('ville_depart_id') == $ville->id)>{{ $ville->ville }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Arrivée</label>
        <select name="ville_arrivee_id" class="form-select">
            <option value="">Toutes</option>
            @foreach ($villes as $ville)
                <option value="{{ $ville->id }}" @selected($f('ville_arrivee_id') == $ville->id)>{{ $ville->ville }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Type</label>
        <select name="type_voyage_id" class="form-select">
            <option value="">Tous</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected($f('type_voyage_id') == $type->id)>{{ $type->type_voyage }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Départ du</label>
        <input type="date" name="date_from" value="{{ $f('date_from') }}" class="form-control">
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">au</label>
        <input type="date" name="date_to" value="{{ $f('date_to') }}" class="form-control">
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select">
            <option value="">Tous</option>
            <option value="a_venir" @selected($f('statut') === 'a_venir')>À venir</option>
            <option value="passe" @selected($f('statut') === 'passe')>Passés</option>
        </select>
    </div>
</x-admin.filters>

<x-admin.card flush>
    @if ($voyages->isEmpty())
        <x-admin.empty icon="bi-signpost-split" title="Aucun voyage ne correspond à ces filtres" text="Modifiez les filtres ou programmez un nouveau voyage." />
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead>
                    <tr>
                        <th>Trajet</th>
                        <th>Départ</th>
                        <th>Arrivée</th>
                        <th>Autocar</th>
                        <th>Remplissage</th>
                        <th class="text-end">Prix</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($voyages as $voyage)
                        @php
                            $rate = $voyage->occupancyRate();
                            $past = $voyage->isPast();
                        @endphp
                        <tr class="{{ $past ? 'is-past' : '' }}">
                            <td>
                                <div class="sa-route">{{ $voyage->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $voyage->villeArrivee?->ville }}</div>
                                <div class="d-flex gap-1 mt-1">
                                    @if ($past)
                                        <span class="sa-chip muted">Passé</span>
                                    @else
                                        <span class="sa-chip success dot">À venir</span>
                                    @endif
                                    @if ($voyage->typeVoyage)
                                        <span class="sa-chip brand">{{ $voyage->typeVoyage->type_voyage }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="sa-num">
                                <div class="sa-strong">{{ \Carbon\Carbon::parse($voyage->date_depart)->format('d/m/Y') }}</div>
                                <div class="sa-sub">{{ \Carbon\Carbon::parse($voyage->heure_depart)->format('H:i') }}</div>
                            </td>
                            <td class="sa-num">
                                <div>{{ \Carbon\Carbon::parse($voyage->date_arrivee)->format('d/m/Y') }}</div>
                                <div class="sa-sub">{{ \Carbon\Carbon::parse($voyage->heure_arrivee)->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="sa-mono">{{ $voyage->autocar?->matricule }}</div>
                                <div class="sa-sub">{{ $voyage->autocar?->societe?->raison_social }}</div>
                            </td>
                            <td>
                                <div class="sa-occupancy">
                                    <div class="progress"><div class="progress-bar {{ $rate >= 80 ? 'bg-success' : ($rate >= 40 ? '' : 'bg-warning') }}" style="width: {{ $rate }}%"></div></div>
                                    <a href="{{ route('reservation.admin.index', ['ville_depart_id' => $voyage->ville_depart_id, 'ville_arrivee_id' => $voyage->ville_arrivee_id, 'date_from' => $voyage->date_depart, 'date_to' => $voyage->date_depart]) }}"
                                       title="Voir les réservations">{{ $voyage->reservations_count }}/{{ $voyage->autocar?->nbr_siege ?? 0 }}</a>
                                </div>
                            </td>
                            <td class="text-end sa-num sa-strong text-nowrap">{{ number_format($voyage->prix, 2, ',', ' ') }} DH</td>
                            <td class="text-end">
                                <x-admin.row-actions
                                    :edit="$canUpdate ? route('voyages.edit', $voyage->id) : null"
                                    :delete="$canDelete ? route('voyages.destroy', $voyage->id) : null"
                                    :confirm="'Supprimer le voyage ' . $voyage->villeDepart?->ville . ' → ' . $voyage->villeArrivee?->ville . ' ?'">
                                    <a href="{{ route('voyages.passagers', $voyage->id) }}" class="sa-icon-btn" title="Liste des passagers"><i class="bi bi-people"></i></a>
                                    @if ($user->hasPermission('voyages.create'))
                                        <a href="{{ route('voyages.programmer', $voyage->id) }}" class="sa-icon-btn" title="Programmer sur d'autres jours"><i class="bi bi-calendar-plus"></i></a>
                                    @endif
                                </x-admin.row-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$voyages" label="voyage(s)" />
    @endif
</x-admin.card>
@endsection
