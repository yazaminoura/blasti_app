@extends('admin.Layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <a href="{{ route('chauffeur.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="bi bi-arrow-left me-1"></i>Retour aux départs
            </a>
            <h1 class="h3 mb-0 text-gray-800">
                Feuille de Route : {{ $voyage->villeDepart?->ville }} <i class="bi bi-arrow-right text-muted mx-1"></i> {{ $voyage->villeArrivee?->ville }}
            </h1>
            <p class="text-muted small mb-0">
                Départ le <strong>{{ \Carbon\Carbon::parse($voyage->date_depart)->translatedFormat('l d F Y') }}</strong> à <strong>{{ \Carbon\Carbon::parse($voyage->heure_depart)->format('H:i') }}</strong>
                · Autocar : <strong>{{ $voyage->autocar?->matricule }}</strong> ({{ $voyage->autocar?->societe?->raison_social ?? 'Compagnie' }})
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalRetard">
                <i class="bi bi-clock-history me-1"></i>Signaler / Modifier un retard
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($voyage->estEnRetard())
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
            <div>
                <strong>Départ signalé en retard de {{ $voyage->retard_minutes }} minutes.</strong>
                @if ($voyage->motif_retard)
                    <span class="d-block small text-muted">Motif : {{ $voyage->motif_retard }}</span>
                @endif
                <span class="d-block small text-dark mt-1">
                    Nouvelle heure de départ estimée : <strong>{{ $voyage->heureDepartEstimee() }}</strong> · Arrivée estimée : <strong>{{ $voyage->heureArriveeEstimee() }}</strong>
                </span>
            </div>
        </div>
    @endif

    <!-- Operational counters (strictly anonymous) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white text-center p-3">
                <div class="fs-4 fw-bold">{{ $stats['total_clients'] }} / {{ $stats['capacite'] }}</div>
                <div class="small opacity-75">Passagers prévus</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white text-center p-3">
                <div class="fs-4 fw-bold">{{ $stats['a_bord'] }}</div>
                <div class="small opacity-75">Passagers à bord</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-info text-white text-center p-3">
                <div class="fs-4 fw-bold">{{ $stats['restant_a_embarquer'] }}</div>
                <div class="small opacity-75">Restant à monter</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-dark text-white text-center p-3">
                <div class="fs-4 fw-bold">{{ $stats['taux_remplissage'] }}%</div>
                <div class="small opacity-75">Taux de remplissage</div>
            </div>
        </div>
    </div>

    <!-- Stops & Passenger Flow -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-geo-alt text-primary me-2"></i>Itinéraire et flux de passagers par arrêt
            </h5>
            <small class="text-muted">Horaires de passage et nombre de montées / descentes prévues.</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Ordre</th>
                            <th>Ville / Arrêt</th>
                            <th>Horaire prévu</th>
                            @if ($voyage->estEnRetard())
                                <th>Horaire révisé</th>
                            @endif
                            <th class="text-center text-success">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Montées
                            </th>
                            <th class="text-center text-danger">
                                <i class="bi bi-box-arrow-right me-1"></i>Descentes
                            </th>
                            <th class="text-center text-primary pe-3">
                                <i class="bi bi-people-fill me-1"></i>À bord après l'arrêt
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['arrets'] as $arret)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge rounded-pill bg-light text-dark border">
                                        {{ $arret['ordre'] + 1 }}
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">{{ $arret['ville'] }}</td>
                                <td>{{ $arret['passage_prevu'] }}</td>
                                @if ($voyage->estEnRetard())
                                    <td class="text-danger fw-bold">{{ $arret['passage_estime'] }}</td>
                                @endif
                                <td class="text-center">
                                    @if ($arret['montees'] > 0)
                                        <span class="badge bg-success-subtle text-success px-2 py-1 fs-6">
                                            +{{ $arret['montees'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($arret['descentes'] > 0)
                                        <span class="badge bg-danger-subtle text-danger px-2 py-1 fs-6">
                                            -{{ $arret['descentes'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3 fw-bold text-primary">
                                    {{ $arret['passagers_a_bord'] }} passager(s)
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Anonymized Seat Grid -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-grid-3x3-gap text-primary me-2"></i>Occupation des sièges (Anonymisé)
                </h5>
                <small class="text-muted">Aperçu visuel des sièges occupés et des passagers déjà à bord (sans coordonnées privées).</small>
            </div>
            <div class="d-flex gap-3 small">
                <span class="d-flex align-items-center"><span class="badge bg-success me-1">&nbsp;</span> Libre</span>
                <span class="d-flex align-items-center"><span class="badge bg-primary me-1">&nbsp;</span> Réservé</span>
                <span class="d-flex align-items-center"><span class="badge bg-warning text-dark me-1">&nbsp;</span> À bord</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row row-cols-4 row-cols-sm-6 row-cols-md-8 row-cols-lg-10 g-2 text-center">
                @foreach ($stats['sieges'] as $siege)
                    @php
                        $colorClass = match($siege['statut']) {
                            'embarque' => 'btn-warning text-dark',
                            'reserve' => 'btn-primary',
                            default => 'btn-outline-success',
                        };
                    @endphp
                    <div class="col">
                        <div class="btn btn-sm w-100 {{ $colorClass }}" style="cursor: default;" title="{{ $siege['segment'] ?? 'Place libre' }}">
                            <div class="fw-bold fs-6">{{ $siege['numero'] }}</div>
                            <div style="font-size: 0.65rem;" class="text-truncate">
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
        </div>
    </div>
</div>

<!-- Modal Signalement Retard -->
<div class="modal fade" id="modalRetard" tabindex="-1" aria-labelledby="modalRetardLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('chauffeur.retard', $voyage) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRetardLabel">
                        <i class="bi bi-clock-history me-1 text-warning"></i>Signaler un retard
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
                               placeholder="Ex : Embouteillage autoroute, panne mécanique..." 
                               value="{{ old('motif_retard', $voyage->motif_retard) }}" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold">Enregistrer le retard</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
