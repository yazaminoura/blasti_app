@extends('admin.Layout.app')
@section('title', 'Feuille de route · ' . $voyage->villeDepart?->ville . ' → ' . $voyage->villeArrivee?->ville)

@section('content')
@php
    $d = $voyage->departAt();
@endphp

<x-admin.page-header :title="'Feuille de route · ' . $voyage->villeDepart?->ville . ' → ' . $voyage->villeArrivee?->ville"
                     :subtitle="$d->translatedFormat('l d F Y') . ' à ' . $d->format('H:i') . ' · ' . ($voyage->autocar?->societe?->raison_social ?? 'Compagnie') . ' · ' . $voyage->autocar?->matricule"
                     :back="route('chauffeur.index')"
                     backLabel="Espace Chauffeur">
    <button type="button" class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#modalRetard">
        <i class="bi bi-clock-history"></i> {{ $voyage->estEnRetard() ? 'Modifier le retard' : 'Signaler un retard' }}
    </button>
    <button type="button" class="btn btn-soft" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
</x-admin.page-header>

@if ($voyage->estEnRetard())
    <div class="alert alert-warning d-flex align-items-center mb-3">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
        <div>
            <strong>Départ retardé de {{ $voyage->retard_minutes }} minutes</strong>
            @if ($voyage->motif_retard) · <span class="text-muted">{{ $voyage->motif_retard }}</span>@endif
            <div class="small mt-1">
                Estimation révisée : Départ à <strong>{{ $voyage->heureDepartEstimee() }}</strong> · Arrivée à <strong>{{ $voyage->heureArriveeEstimee() }}</strong>
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <x-admin.stat label="Passagers prévus" :value="$stats['total_clients'] . ' / ' . $stats['capacite']" icon="bi-people" />
    </div>
    <div class="col-md-3 col-6">
        <x-admin.stat label="À bord" :value="$stats['a_bord']" icon="bi-person-check" tone="success" />
    </div>
    <div class="col-md-3 col-6">
        <x-admin.stat label="Restant à monter" :value="$stats['restant_a_embarquer']" icon="bi-box-arrow-in-right" tone="warning" />
    </div>
    <div class="col-md-3 col-6">
        <x-admin.stat label="Remplissage" :value="$stats['taux_remplissage'] . '%'" icon="bi-bar-chart" tone="info" />
    </div>
</div>

<x-admin.card title="Itinéraire et flux de passagers par arrêt" class="mb-3">
    <div class="table-responsive">
        <table class="table sa-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">N°</th>
                    <th>Ville / Arrêt</th>
                    <th>Horaire prévu</th>
                    @if ($voyage->estEnRetard())
                        <th>Horaire révisé</th>
                    @endif
                    <th class="text-center">Montées</th>
                    <th class="text-center">Descentes</th>
                    <th class="text-center pe-3">Passagers à bord</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stats['arrets'] as $arret)
                    <tr>
                        <td class="sa-num">{{ $arret['ordre'] + 1 }}</td>
                        <td>
                            <div class="sa-strong">{{ $arret['ville'] }}</div>
                        </td>
                        <td class="sa-num">{{ $arret['passage_prevu'] }}</td>
                        @if ($voyage->estEnRetard())
                            <td class="sa-num text-danger fw-bold">{{ $arret['passage_estime'] }}</td>
                        @endif
                        <td class="text-center">
                            @if ($arret['montees'] > 0)
                                <span class="sa-chip success">+{{ $arret['montees'] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($arret['descentes'] > 0)
                                <span class="sa-chip danger">-{{ $arret['descentes'] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center pe-3 sa-num sa-strong">
                            {{ $arret['passagers_a_bord'] }} passager(s)
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin.card>

<x-admin.card title="Plan d'occupation des sièges (Anonymisé)">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
        <div class="sa-sub">
            Aperçu visuel des sièges réservés et des passagers déjà à bord (sans coordonnées privées).
        </div>
        <div class="d-flex gap-3 small">
            <span class="d-flex align-items-center"><span class="badge bg-success me-1">&nbsp;</span> Libre</span>
            <span class="d-flex align-items-center"><span class="badge bg-primary me-1">&nbsp;</span> Réservé</span>
            <span class="d-flex align-items-center"><span class="badge bg-warning text-dark me-1">&nbsp;</span> À bord</span>
        </div>
    </div>

    <div class="row row-cols-4 row-cols-sm-6 row-cols-md-8 row-cols-lg-10 g-2 text-center">
        @foreach ($stats['sieges'] as $siege)
            @php
                $colorClass = match($siege['statut']) {
                    'embarque' => 'btn-warning text-dark',
                    'reserve' => 'btn-primary',
                    default => 'btn-soft',
                };
            @endphp
            <div class="col">
                <div class="btn btn-sm w-100 {{ $colorClass }}" style="cursor: default;" title="{{ $siege['segment'] ?? 'Place libre' }}">
                    <div class="sa-num fw-bold fs-6">{{ $siege['numero'] }}</div>
                    <div style="font-size: 0.7rem;" class="text-truncate">
                        @if ($siege['statut'] === 'embarque')
                            À bord
                        @elseif ($siege['statut'] === 'reserve')
                            Occupé
                        @else
                            Libre
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-admin.card>

{{-- Modal Signalement Retard --}}
<div class="modal fade" id="modalRetard" tabindex="-1" aria-labelledby="modalRetardLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('chauffeur.retard', $voyage) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRetardLabel">
                        <i class="bi bi-clock-history me-1 text-warning"></i>Signaler / Modifier un retard
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Indiquez l'estimation du retard en minutes. Ce retard sera visible sur la feuille de route et l'affichage voyageur.
                    </p>
                    <div class="mb-3">
                        <label for="retard_minutes" class="form-label fw-bold">Retard estimé (en minutes) :</label>
                        <input type="number" name="retard_minutes" id="retard_minutes" class="form-control" 
                               min="0" max="360" value="{{ old('retard_minutes', $voyage->retard_minutes) }}" required>
                        <small class="text-muted">Mettez 0 pour annuler le retard et revenir à l'heure normale.</small>
                    </div>
                    <div class="mb-3">
                        <label for="motif_retard" class="form-label fw-bold">Motif (optionnel) :</label>
                        <input type="text" name="motif_retard" id="motif_retard" class="form-control" 
                               placeholder="Ex : Embouteillage autoroute, incident météo..." 
                               value="{{ old('motif_retard', $voyage->motif_retard) }}" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer le retard</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
