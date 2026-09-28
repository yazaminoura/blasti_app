<x-app-layout>
    @include('partials.page-banner', [
        'title' => __('Nos compagnies partenaires'),
        'subtitle' => __('Des sociétés de transport identifiées, leurs services à bord et leurs prochains départs.'),
    ])

    <section class="section">
        <div class="container">
            <div class="row g-4">
                @forelse ($societes as $societe)
                    <div class="col-xl-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    @if ($societe->logo)
                                        <img src="{{ asset('storage/' . $societe->logo) }}" alt="{{ $societe->raison_social }}" class="rounded-3 border p-1 bg-white" style="width: 64px; height: 64px; object-fit: contain;">
                                    @else
                                        <span class="rounded-3 d-grid text-white fw-bold fs-4" style="width: 64px; height: 64px; place-items: center; background: linear-gradient(135deg, var(--brand-900), var(--brand));">
                                            {{ mb_strtoupper(mb_substr($societe->raison_social, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div>
                                        <h5 class="mb-1">{{ $societe->raison_social }}</h5>
                                        <span class="fs-14 text-muted"><i class="isax isax-location me-1"></i>{{ __($societe->ville) }}</span>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-3 fs-14 mb-3">
                                    <span><i class="isax isax-bus text-primary me-1"></i>{{ $societe->autocars_count }} {{ __('autocars') }}</span>
                                    <span><i class="isax isax-calendar-tick text-primary me-1"></i>{{ $societe->departs_count }} {{ __('départs à venir') }}</span>
                                    @if ($societe->prix_min)
                                        <span><i class="isax isax-tag text-primary me-1"></i>{{ __('dès') }} {{ number_format($societe->prix_min, 0, ',', ' ') }} {{ __('DHS') }}</span>
                                    @endif
                                </div>

                                @if ($societe->services->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        @foreach ($societe->services->take(6) as $service)
                                            <span class="badge bg-primary-transparent text-primary fw-medium px-2 py-1">{{ __($service) }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <a href="{{ route('client.societes.showVoyageSociete.index', $societe->id) }}" class="btn btn-primary mt-auto">
                                    {{ __('Voir les départs') }} <i class="isax isax-arrow-right-3 ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted py-5">{{ __('Aucune compagnie pour le moment.') }}</p>
                @endforelse
            </div>

            <div class="mz-numbers mt-5 text-center">
                <h4 class="text-white mb-2">{{ __('Vous êtes une société de transport ?') }}</h4>
                <p class="mb-3" style="color: rgba(255,255,255,.85);">{{ __('Vendez vos places en ligne sur :nom : écrivez-nous pour rejoindre la plateforme.', ['nom' => config('safar.nom')]) }}</p>
                <a href="{{ route('contact') }}" class="btn btn-light">{{ __('Nous contacter') }}</a>
            </div>
        </div>
    </section>
</x-app-layout>
