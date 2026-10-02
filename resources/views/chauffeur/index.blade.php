@extends('admin.Layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="bi bi-compass text-primary me-2"></i>Espace Chauffeur
            </h1>
            <p class="text-muted small mb-0">Consultez vos départs, les horaires d'arrivée, les arrêts et le nombre de passagers prévus.</p>
        </div>
        <div>
            <div class="btn-group" role="group">
                <a href="{{ route('chauffeur.index', ['statut' => 'a_venir']) }}" class="btn btn-sm {{ $onglet !== 'passes' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-calendar-event me-1"></i>À venir & Aujourd'hui
                </a>
                <a href="{{ route('chauffeur.index', ['statut' => 'passes']) }}" class="btn btn-sm {{ $onglet === 'passes' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-clock-history me-1"></i>Départs passés
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($voyages->isEmpty())
        <div class="card shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-bus-front display-4 text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Aucun départ assigné dans cette catégorie</h5>
                <p class="text-muted small">Vos prochains voyages planifiés apparaîtront ici.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($voyages as $v)
                @php
                    $occupes = $v->reservations_count;
                    $total = (int) ($v->autocar?->nbr_siege ?? 0);
                    $taux = $total > 0 ? min(100, round(($occupes * 100) / $total)) : 0;
                    $isToday = \Carbon\Carbon::parse($v->date_depart)->isToday();
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card shadow-sm h-100 border-start border-4 {{ $isToday ? 'border-primary' : 'border-secondary' }}">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge {{ $isToday ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ \Carbon\Carbon::parse($v->date_depart)->translatedFormat('l d M Y') }}
                                    </span>
                                    @if ($v->estEnRetard())
                                        <span class="badge bg-warning text-dark ms-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>+{{ $v->retard_minutes }} min
                                        </span>
                                    @endif
                                </div>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-bus-front me-1"></i>{{ $v->autocar?->matricule ?? 'Autocar' }}
                                </span>
                            </div>

                            <h5 class="card-title fw-bold text-dark my-2">
                                {{ $v->villeDepart?->ville }} <i class="bi bi-arrow-right text-muted mx-1"></i> {{ $v->villeArrivee?->ville }}
                            </h5>

                            <div class="d-flex justify-content-between text-muted small my-2 py-2 border-top border-bottom">
                                <div>
                                    <i class="bi bi-clock me-1 text-primary"></i>Départ : 
                                    <strong class="text-dark">{{ \Carbon\Carbon::parse($v->heure_depart)->format('H:i') }}</strong>
                                    @if ($v->estEnRetard())
                                        <small class="text-danger fw-bold">({{ $v->heureDepartEstimee() }})</small>
                                    @endif
                                </div>
                                <div>
                                    <i class="bi bi-flag me-1 text-success"></i>Arrivée : 
                                    <strong class="text-dark">{{ \Carbon\Carbon::parse($v->heure_arrivee)->format('H:i') }}</strong>
                                    @if ($v->estEnRetard())
                                        <small class="text-danger fw-bold">({{ $v->heureArriveeEstimee() }})</small>
                                    @endif
                                </div>
                            </div>

                            <div class="my-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted"><i class="bi bi-people me-1"></i>Passagers :</span>
                                    <strong class="text-dark">{{ $occupes }} / {{ $total }} places</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar {{ $taux > 80 ? 'bg-danger' : ($taux > 50 ? 'bg-primary' : 'bg-success') }}" 
                                         role="progressbar" style="width: {{ $taux }}%"></div>
                                </div>
                            </div>

                            <div class="text-muted small mt-1">
                                <i class="bi bi-geo-alt me-1"></i>{{ $v->arrets->count() }} arrêt(s) sur l'itinéraire
                            </div>

                            <div class="mt-auto pt-3">
                                <a href="{{ route('chauffeur.show', $v) }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-eye me-1"></i>Feuille de route & Arrêts
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $voyages->links() }}
        </div>
    @endif
</div>
@endsection
