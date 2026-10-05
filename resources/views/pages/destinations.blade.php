<x-app-layout>
    @include('partials.page-banner', [
        'title' => __('Nos destinations'),
        'subtitle' => __('Toutes les villes desservies par nos compagnies partenaires, et où aller depuis chacune.'),
    ])

    <section class="section">
        <div class="container">
            @if ($villes->isEmpty())
                <p class="text-center text-muted py-5">{{ __('Aucun départ programmé pour le moment') }}</p>
            @endif
            <div class="row g-4">
                @foreach ($villes as $ville)
                    @php
                        $depuis = ($routes[$ville->id] ?? collect())->groupBy('ville_arrivee_id')
                            ->map(fn ($trips) => ['ville' => $trips->first()->villeArrivee, 'prix' => $trips->min('prix'), 'nb' => $trips->count()])
                            ->sortBy('prix');
                    @endphp
                    <div class="col-xl-4 col-md-6">
                        <div class="card h-100 overflow-hidden border-0 shadow-sm">
                            <a href="{{ route('voyages.client.index', ['ville_depart' => $ville->id]) }}" class="d-block position-relative">
                                @if ($ville->photo_url)
                                    <img src="{{ $ville->photo_url }}" alt="{{ __($ville->ville) }}" loading="lazy" style="height: 190px; width: 100%; object-fit: cover;"
                                         onerror="this.onerror=null; this.src='{{ \App\Support\BrandImages::url('blasti-hero-bus-bg.png') }}';">
                                @else
                                    <div class="d-flex align-items-center justify-content-center text-white" style="height: 190px; background: linear-gradient(135deg, var(--brand-900), var(--brand));">
                                        <i class="isax isax-location" style="font-size: 3rem; opacity: .4;"></i>
                                    </div>
                                @endif
                                <span class="position-absolute bottom-0 start-0 m-3 badge bg-white text-dark fs-15 fw-semibold px-3 py-2">{{ __($ville->ville) }}</span>
                            </a>
                            <div class="card-body">
                                <div class="d-flex gap-3 fs-14 text-muted mb-3">
                                    <span><i class="isax isax-export-1 text-primary me-1"></i>{{ $ville->departs_count }} {{ __('départs') }}</span>
                                    <span><i class="isax isax-import-1 text-primary me-1"></i>{{ $ville->arrivees_count }} {{ __('arrivées') }}</span>
                                </div>
                                @if ($depuis->isNotEmpty())
                                    <h6 class="fs-14 mb-2">{{ __('Au départ de :ville', ['ville' => __($ville->ville)]) }}</h6>
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($depuis->take(4) as $dest)
                                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom fs-14">
                                                <a href="{{ route('voyages.client.index', ['ville_depart' => $ville->id, 'ville_arrivee' => $dest['ville']->id]) }}">
                                                    <i class="isax isax-arrow-right-3 me-1"></i>{{ __($dest['ville']->ville) }}
                                                </a>
                                                <span class="text-muted">{{ __('dès') }} <strong class="text-primary">{{ number_format($dest['prix'], 0, ',', ' ') }} {{ __('DHS') }}</strong></span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="fs-14 text-muted mb-0">{{ __('Pas de départ programmé depuis cette ville pour le moment.') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-app-layout>
