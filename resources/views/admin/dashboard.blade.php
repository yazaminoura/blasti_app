@extends('admin.Layout.app')
@section('title', 'Tableau de bord')

@section('content')
@php
    $me = auth()->user();
    $hour = now()->hour;
    $greeting = $hour < 18 ? 'Bonjour' : 'Bonsoir';
    $fleetFree = $totalAutocars - $busyAutocars;
@endphp

<x-admin.page-header :title="$greeting . ', ' . \Illuminate\Support\Str::before($me->name, ' ')"
                     :subtitle="ucfirst(now()->locale('fr')->isoFormat('dddd D MMMM YYYY')) . ' · voici l\'activité de ' . config('safar.nom') . '.'">
    <div class="dropdown">
        <button type="button" class="btn btn-soft dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> Exporter</button>
        <div class="dropdown-menu dropdown-menu-end sa-dropdown">
            <a class="dropdown-item" href="{{ route('admin.export.reservations') }}"><i class="bi bi-ticket-perforated"></i> Réservations</a>
            <a class="dropdown-item" href="{{ route('admin.export.voyages') }}"><i class="bi bi-signpost-split"></i> Voyages</a>
            <a class="dropdown-item" href="{{ route('admin.export.users') }}"><i class="bi bi-people"></i> Utilisateurs</a>
            <a class="dropdown-item" href="{{ route('admin.export.autocars') }}"><i class="bi bi-bus-front"></i> Autocars</a>
        </div>
    </div>
    @if ($me->hasPermission('voyages.create'))
        <a href="{{ route('voyages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nouveau voyage</a>
    @endif
</x-admin.page-header>

{{-- setup reminders for the super admin: still the example contact details, or e-mails not really sent --}}
@if ($me->isSuperAdmin())
    @php
        $todo = array_filter([
            blank(\App\Models\Parametre::actuel()->telephone) ? ['Les coordonnées affichées aux clients sont encore des exemples.', route('admin.coordonnees.edit'), 'Mettre les vraies'] : null,
            in_array(config('mail.default'), ['log', 'array'], true) ? ['Les e-mails (billets, rappels, confirmations) ne sont pas envoyés : aucun serveur SMTP dans .env.', route('admin.coordonnees.edit'), 'Voir comment'] : null,
            ! \App\Support\Cmi::enabled() ? ['Le paiement par carte (CMI) n\'est pas configuré : les clients paient à l\'embarquement.', null, null] : null,
        ]);
    @endphp
    @if ($todo)
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="bi bi-list-check"></i> Avant la mise en ligne</div>
            <ul class="mb-0 ps-3">
                @foreach ($todo as [$text, $link, $label])
                    <li>{{ $text }} @if ($link)<a href="{{ $link }}" class="fw-semibold">{{ $label }}</a>@endif</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <x-admin.stat label="Chiffre d'affaires" :value="number_format($totalRevenue, 0, ',', ' ') . ' DH'" icon="bi-cash-stack" tone="success">
            <x-admin.trend :value="$revenueTrend" /> · ce mois {{ number_format($revenueThisMonth, 0, ',', ' ') }} DH
        </x-admin.stat>
    </div>
    <div class="col-xl-3 col-sm-6">
        <x-admin.stat label="Réservations" :value="number_format($totalReservations, 0, ',', ' ')" icon="bi-ticket-perforated">
            <x-admin.trend :value="$reservationsTrend" />
        </x-admin.stat>
    </div>
    <div class="col-xl-3 col-sm-6">
        <x-admin.stat label="Voyages" :value="$totalVoyages" icon="bi-signpost-split" tone="info">
            <span class="sa-chip success dot">{{ $upcomingVoyages }} à venir</span>
        </x-admin.stat>
    </div>
    <div class="col-xl-3 col-sm-6">
        <x-admin.stat label="Clients" :value="number_format($totalUsers, 0, ',', ' ')" icon="bi-people" tone="warning">
            <x-admin.trend :value="$usersTrend" label="d'inscriptions" />
        </x-admin.stat>
    </div>
</div>

{{-- Charts --}}
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <x-admin.card title="Réservations par mois" subtitle="Nombre de places vendues" icon="bi-graph-up" class="h-100">
            <div style="height: 290px;"><canvas id="reservationsChart"></canvas></div>
        </x-admin.card>
    </div>
    <div class="col-xl-4">
        <x-admin.card title="Top destinations" subtitle="Villes d'arrivée les plus réservées" icon="bi-geo-alt" class="h-100">
            @if ($topDestinations->isEmpty())
                <x-admin.empty icon="bi-geo-alt" title="Pas encore de données" />
            @else
                <div style="height: 180px;"><canvas id="destinationsChart"></canvas></div>
                <ul class="list-unstyled mb-0 mt-3" id="destinationsLegend">
                    @foreach ($topDestinations as $dest)
                        <li class="d-flex align-items-center justify-content-between py-1 small">
                            <span class="d-flex align-items-center gap-2"><span class="sa-legend-dot" data-index="{{ $loop->index }}"></span> {{ $dest['name'] ?? $dest->name }}</span>
                            <span class="sa-strong sa-num">{{ $dest['count'] ?? $dest->count }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Next departures --}}
    <div class="col-xl-8">
        <x-admin.card title="Prochains départs" icon="bi-calendar-event" flush class="h-100">
            <x-slot:actions>
                <a href="{{ route('voyages.index', ['statut' => 'a_venir']) }}" class="btn btn-soft btn-sm">Tous les voyages à venir</a>
            </x-slot:actions>
            @if ($nextDepartures->isEmpty())
                <x-admin.empty icon="bi-calendar-x" title="Aucun départ programmé" />
            @else
                <div class="sa-table-wrap">
                    <table class="table sa-table align-middle">
                        <thead>
                            <tr>
                                <th>Trajet</th>
                                <th>Départ</th>
                                <th>Autocar</th>
                                <th>Remplissage</th>
                                <th class="text-end">Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($nextDepartures as $voyage)
                                @php $rate = $voyage->occupancyRate(); @endphp
                                <tr>
                                    <td><span class="sa-route">{{ $voyage->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $voyage->villeArrivee?->ville }}</span></td>
                                    <td class="sa-num text-nowrap">{{ \Carbon\Carbon::parse($voyage->date_depart)->format('d/m') }} <span class="sa-sub">{{ \Carbon\Carbon::parse($voyage->heure_depart)->format('H:i') }}</span></td>
                                    <td class="sa-mono">{{ $voyage->autocar?->matricule }}</td>
                                    <td>
                                        <div class="sa-occupancy">
                                            <div class="progress"><div class="progress-bar {{ $rate >= 80 ? 'bg-success' : ($rate >= 40 ? '' : 'bg-warning') }}" style="width: {{ $rate }}%"></div></div>
                                            <span>{{ $voyage->reservations_count }}/{{ $voyage->autocar?->nbr_siege ?? 0 }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end sa-num sa-strong text-nowrap">{{ number_format($voyage->prix, 0, ',', ' ') }} DH</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.card>
    </div>

    {{-- Fleet --}}
    <div class="col-xl-4">
        <x-admin.card title="Flotte" icon="bi-bus-front" class="h-100">
            <div class="d-flex align-items-end gap-2 mb-3">
                <span class="sa-stat-value m-0">{{ $totalAutocars }}</span>
                <span class="text-body-secondary pb-1">autocars</span>
            </div>
            <div class="d-grid gap-2">
                <div class="sa-kpi-mini"><span>Avec un voyage programmé</span><span class="sa-chip success">{{ $busyAutocars }}</span></div>
                <div class="sa-kpi-mini"><span>Sans voyage programmé</span><span class="sa-chip warning">{{ $fleetFree }}</span></div>
                <div class="sa-kpi-mini flex-column align-items-stretch">
                    <div class="d-flex justify-content-between"><span>Remplissage moyen</span><span class="sa-strong">{{ $averageOccupancy }}%</span></div>
                    <div class="progress mt-2"><div class="progress-bar" style="width: {{ $averageOccupancy }}%"></div></div>
                    <span class="sa-sub mt-1">Sur les voyages à venir</span>
                </div>
            </div>
            <x-slot:footer>
                <a href="{{ route('autocars.index') }}" class="btn btn-soft w-100">Gérer la flotte <i class="bi bi-arrow-right"></i></a>
            </x-slot:footer>
        </x-admin.card>
    </div>
</div>

{{-- Latest bookings --}}
<x-admin.card title="Réservations récentes" icon="bi-clock-history" flush>
    <x-slot:actions>
        <a href="{{ route('reservation.admin.index') }}" class="btn btn-soft btn-sm">Voir tout</a>
    </x-slot:actions>
    @if ($recentReservations->isEmpty())
        <x-admin.empty icon="bi-inbox" title="Aucune réservation pour le moment" />
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Trajet</th>
                        <th>Réservé le</th>
                        <th>Siège</th>
                        <th class="text-end">Prix</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentReservations as $reservation)
                        <tr>
                            <td>
                                <div class="sa-person">
                                    <span class="sa-avatar">{{ mb_substr($reservation->user?->name ?? '?', 0, 2) }}</span>
                                    <div>
                                        <div class="sa-strong">{{ $reservation->user?->name ?? '—' }}</div>
                                        <div class="sa-sub">{{ $reservation->user?->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="sa-route">{{ $reservation->villeDepart?->ville }} <i class="bi bi-arrow-right"></i> {{ $reservation->villeArrivee?->ville }}</span></td>
                            <td class="sa-num">{{ $reservation->created_at->format('d/m/Y') }}</td>
                            <td><span class="sa-chip brand">N° {{ $reservation->num_siege }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('reservation.admin.show', $reservation->id) }}" class="sa-strong sa-num text-nowrap">{{ number_format($reservation->prix, 2, ',', ' ') }} DH</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.card>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var months = @json($months);
        var counts = @json($reservationCounts);
        var destNames = @json($topDestinations->pluck('name'));
        var destCounts = @json($topDestinations->pluck('count'));
        var charts = [];

        function palette() {
            var brand = saColor('--brand');
            // brand first, then companions far from any brand hue, readable in both themes
            return [brand, '#f79009', '#7a5af8', '#ee46bc', '#98a2b3'];
        }

        function draw() {
            charts.forEach(function (c) { c.destroy(); });
            charts = [];
            var text = saColor('--sa-muted');
            var grid = saColor('--sa-border');
            var brand = saColor('--brand');
            var brandRgb = saColor('--brand-rgb');
            Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
            Chart.defaults.color = text;

            var lineCtx = document.getElementById('reservationsChart');
            if (lineCtx) {
                var gradient = lineCtx.getContext('2d').createLinearGradient(0, 0, 0, 290);
                gradient.addColorStop(0, 'rgba(' + brandRgb + ', .28)');
                gradient.addColorStop(1, 'rgba(' + brandRgb + ', 0)');
                charts.push(new Chart(lineCtx, {
                    type: 'line',
                    data: { labels: months, datasets: [{ label: 'Réservations', data: counts, borderColor: brand, backgroundColor: gradient, fill: true, tension: .35, borderWidth: 2.5, pointRadius: 3, pointHoverRadius: 6, pointBackgroundColor: brand }] },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid }, border: { display: false } },
                            x: { grid: { display: false }, border: { color: grid } }
                        }
                    }
                }));
            }

            var doughnutCtx = document.getElementById('destinationsChart');
            if (doughnutCtx) {
                var colors = palette();
                document.querySelectorAll('#destinationsLegend .sa-legend-dot').forEach(function (dot) {
                    dot.style.background = colors[dot.dataset.index % colors.length];
                });
                charts.push(new Chart(doughnutCtx, {
                    type: 'doughnut',
                    data: { labels: destNames, datasets: [{ data: destCounts, backgroundColor: colors, borderColor: saColor('--sa-surface'), borderWidth: 3, hoverOffset: 4 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } }
                }));
            }
        }

        draw();
        document.addEventListener('sa:theme', draw);
    })();
</script>
@endpush
