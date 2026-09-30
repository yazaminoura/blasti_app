<x-app-layout>
    @include('partials.page-banner', [
        'title' => __('À propos de :nom', ['nom' => config('safar.nom')]),
        'crumb' => __('À propos'),
    ])

    <section class="section">
        <div class="container">
            <div class="row align-items-center g-5 mb-5">
                <div class="col-lg-6">
                    <div class="section-header section-header-four mb-3">
                        <h2 class="mb-3">{!! __('Voyager en bus au Maroc, <span>simplement</span>') !!}</h2>
                    </div>
                    <p class="text-muted">{{ __(':nom réunit les compagnies de bus du Maroc sur un seul site. Vous comparez les départs, vous choisissez votre siège sur le plan de l\'autocar et vous recevez votre billet tout de suite, sans passer au guichet.', ['nom' => config('safar.nom')]) }}</p>
                    @if (mb_strtolower(config('safar.nom')) === 'blasti')
                        {{-- the meaning of the name: only while the site keeps this name (Admin > Apparence) --}}
                        <p class="text-muted mb-0">{{ __('Notre nom vient de la darija « blasti », « ma place » : chaque billet correspond à un siège réservé pour vous.') }}</p>
                    @endif
                </div>
                <div class="col-lg-6">
                    <div class="mz-numbers">
                        <div class="row g-3">
                            @foreach ([
                                [$stats['villes'], __('villes desservies')],
                                [$stats['departs'], __('départs à venir')],
                                [$stats['societes'], __('sociétés partenaires')],
                                [$stats['billets'], __('billets vendus')],
                            ] as [$value, $label])
                                <div class="col-6 mz-number"><strong>{{ number_format($value, 0, ',', ' ') }}</strong><span>{{ $label }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach ([
                    ['isax-ticket-star', __('Votre siège, garanti'), __('La place choisie sur le plan vous est réservée : pas de mauvaise surprise à l\'embarquement.')],
                    ['isax-tag', __('Des prix clairs'), __('Le prix affiché est le prix payé. Aucun frais caché.')],
                    ['isax-shield-tick', __('Des compagnies identifiées'), __('Chaque société partenaire est enregistrée avec son ICE, ses contacts et sa flotte.')],
                    ['isax-message-question', __('Un vrai service client'), __('Une question sur un billet ou un départ ? Notre équipe vous répond.')],
                ] as [$icon, $title, $text])
                    <div class="col-lg-3 col-md-6">
                        <div class="mz-feature">
                            <span class="mz-feature-icon"><i class="isax {{ $icon }}"></i></span>
                            <div><h6>{{ $title }}</h6><p>{{ $text }}</p></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-5">
                <a href="{{ route('voyages.list') }}" class="btn btn-primary me-2">{{ __('Voir les départs') }}</a>
                <a href="{{ route('pages.compagnies') }}" class="btn btn-outline-primary">{{ __('Nos compagnies partenaires') }}</a>
            </div>
        </div>
    </section>
</x-app-layout>
