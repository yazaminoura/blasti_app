<x-app-layout>
    <!-- Hero Section -->
    @php
        // Background videos like mosafir.ma: every clip of public/assets/video/hero/ (≈10 s each, file name order)
        // plays in turn with a crossfade, then the loop starts again. Horizontal and vertical clips mix: they cover the card.
        // Without any file the drawn brand background of .hero-section-four stays.
        $heroVideos = collect(glob(public_path('assets/video/hero/*.{mp4,webm}'), GLOB_BRACE) ?: [])
            ->sort()->values()
            ->map(fn ($path) => asset('assets/video/hero/' . basename($path)) . '?v=' . filemtime($path));
    @endphp
    <section class="hero-section-four {{ $heroVideos->isNotEmpty() ? 'has-video' : '' }}">
        @if ($heroVideos->isNotEmpty())
            {{-- two stacked players: the next clip loads in the hidden one, then fades in (assets/js/blasti-hero.js) --}}
            <div class="bl-hero-videos" data-videos='@json($heroVideos)' aria-hidden="true">
                <video class="bl-hero-video is-on" muted playsinline preload="auto"></video>
                <video class="bl-hero-video" muted playsinline preload="auto"></video>
            </div>
            <span class="bl-hero-shade" aria-hidden="true"></span>
        @endif
        <div class="container">
            <div class="hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-10 col-md-12 mx-auto wow fadeInUp" data-wow-delay="0.3s">
                        <div class="banner-content text-center mx-auto">
                            <h1 class="text-white display-4 mb-2">{!! __('Votre billet de bus :icon partout au Maroc avec :brand', ['brand' => e(config('safar.nom')), 'icon' => '<span class="flight-icon"><img src="'.\App\Support\BrandImages::url('blasti-hero-bus.png').'" class="blasti-hero-bus" style="height: 66px; width: auto; vertical-align: middle;" alt="icon"></span>']) !!}</h1>
                            <p class="text-white mx-auto">{{ __('Comparez les départs des compagnies, choisissez votre siège et recevez votre billet en quelques minutes.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Hero Section -->

    <!-- Banner Search -->
    <section class="banner-search-four">
        <div class="container">
            <div class="banner-form card mb-0">
                <div class="card-body">
                    <div>
                        <div class="tab-content">
                            <div class="tab-pane fade active show" id="flight">
                                <form action="{{ route('voyages.client.index') }}" method="GET" id="search-form">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                        <h6 class="fw-medium fs-16 mb-2">{{ __('Trouvez votre voyage idéal') }}</h6>
                                    </div>
                                    <div class="d-lg-flex">
                                        <div class="d-flex form-info">
                                            {{-- same fields as the results page (labels + real dropdown arrows) --}}
                                            <div class="form-item">
                                                <label class="form-label fs-14 text-default mb-1" for="home_ville_depart">{{ __('De') }}</label>
                                                <select name="ville_depart" id="home_ville_depart" class="form-select border-0 ps-0 fw-medium"
                                                        data-bl-select data-bl-icon="isax-location" data-bl-search="{{ __('Rechercher une ville') }}" data-bl-empty="{{ __('Aucune ville trouvée') }}">
                                                    <option value="">{{ __('Ville de départ') }}</option>
                                                    @foreach ($villesRecherche as $ville)
                                                    <option value="{{ $ville->id }}">{{ __($ville->ville) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-item ps-2 ps-sm-3">
                                                <button type="button" class="bl-swap" data-bl-swap="home_ville_depart,home_ville_arrivee" title="{{ __('Inverser départ et arrivée') }}" aria-label="{{ __('Inverser départ et arrivée') }}"><i class="isax isax-arrow-swap-horizontal"></i></button>
                                                <label class="form-label fs-14 text-default mb-1" for="home_ville_arrivee">{{ __('à') }}</label>
                                                <select name="ville_arrivee" id="home_ville_arrivee" class="form-select border-0 ps-0 fw-medium"
                                                        data-bl-select data-bl-icon="isax-location-tick" data-bl-search="{{ __('Rechercher une ville') }}" data-bl-empty="{{ __('Aucune ville trouvée') }}">
                                                    <option value="">{{ __('Ville d\'arrivée') }}</option>
                                                    @foreach ($villesRecherche as $ville)
                                                    <option value="{{ $ville->id }}">{{ __($ville->ville) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-item">
                                                <label class="form-label fs-14 text-default mb-1" for="date_depart">{{ __('Date de départ') }}</label>
                                                <input type="date" class="form-control" id="date_depart" name="date_depart" min="{{ now()->toDateString() }}"
                                                       data-bl-date data-bl-placeholder="{{ __('Choisir une date') }}" data-bl-clear="{{ __('Effacer') }}" data-bl-today="{{ __("Aujourd'hui") }}" data-bl-prev="{{ __('Mois précédent') }}" data-bl-next="{{ __('Mois suivant') }}">
                                            </div>
                                        </div>

                                        <button type="submit" id="rechercher" class="btn btn-primary search-btn rounded">{{ __('Rechercher') }}</button>
                                    </div>
                                </form>

                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- what the traveller gets, only true promises (payment modes come from Modes de règlement) --}}
            <ul class="bl-trust">
                <li><i class="isax isax-ticket-star"></i>{{ __('Siège choisi sur le plan du bus') }}</li>
                <li><i class="isax isax-document-download"></i>{{ __('Billet PDF avec QR code') }}</li>
                @if ($paiements['carte'] && $paiements['agence'])
                    <li><i class="isax isax-card"></i>{{ __('Carte bancaire ou paiement en agence') }}</li>
                @elseif ($paiements['carte'])
                    <li><i class="isax isax-card"></i>{{ __('Paiement par carte bancaire') }}</li>
                @elseif ($paiements['agence'])
                    <li><i class="isax isax-shop"></i>{{ __('Paiement en agence') }}</li>
                @endif
                <li><i class="isax isax-refresh-left-square"></i>{{ __('Annulation en ligne depuis votre espace') }}</li>
            </ul>
        </div>
    </section>
    <!-- /Banner Search -->


    <!-- Popular routes -->
    @if ($routes->isNotEmpty())
    <section class="section bl-routes pb-0">
        <div class="container">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
                <div class="section-header section-header-four mb-0">
                    <h2 class="mb-2">{!! __('Trajets <span>populaires</span>') !!}</h2>
                    <p class="sub-title mb-0">{{ __('Les lignes avec le plus de départs, au meilleur prix du moment.') }}</p>
                </div>
                <a href="{{ route('voyages.list') }}" class="btn btn-outline-primary rounded-pill px-4">{{ __('Tous les voyages') }} <i class="isax isax-arrow-right-3 ms-1"></i></a>
            </div>
            <div class="row g-3">
                @foreach ($routes as $route)
                    @php $prochain = \Carbon\Carbon::parse($route->prochain->date_depart); @endphp
                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <a href="{{ route('voyages.client.index', ['ville_depart' => $route->depart->id, 'ville_arrivee' => $route->arrivee->id]) }}" class="bl-route">
                            <span class="bl-route-cities">
                                <span>{{ __($route->depart->ville) }}</span>
                                <i class="isax isax-arrow-right-1"></i>
                                <span>{{ __($route->arrivee->ville) }}</span>
                            </span>
                            <span class="bl-route-meta">
                                <span><i class="isax isax-bus"></i>{{ trans_choice('{1} :count départ|[2,*] :count départs', $route->departs, ['count' => $route->departs]) }}</span>
                                <span><i class="isax isax-clock"></i>{{ $prochain->isToday() ? __("Aujourd'hui") : ($prochain->isTomorrow() ? __('Demain') : $prochain->format('d/m')) }} · {{ \Carbon\Carbon::parse($route->prochain->heure_depart)->format('H:i') }}</span>
                            </span>
                            <span class="bl-route-price"><small>{{ __('dès') }}</small> {{ number_format($route->prix, 0, ',', ' ') }} {{ __('DHS') }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
    <!-- /Popular routes -->

 <!-- Place Section -->
<section class="section place-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-10 text-center wow fadeInUp" data-wow-delay="0.2s">
                <div class="section-header section-header-four mb-4 text-center">
                    <h2 class="mb-2">{!! __('Prochains <span>départs</span>') !!}</h2>
                    <p class="sub-title">{{ __("Les bus qui partent bientôt : réservez votre siège avant qu'ils soient complets.") }}</p>
                </div>
            </div>
        </div>

        @php
            // Voyages in the wishlist, loaded once (not 2 queries per card)
            $wishlistIds = Auth::check() ? Auth::user()->wishlists()->pluck('voyage_id')->all() : [];
        @endphp
        @if ($voyages->isEmpty())
            <div class="text-center py-5">
                <i class="isax isax-bus text-primary" style="font-size: 3rem;"></i>
                <h5 class="mt-3 mb-1">{{ __('Aucun départ programmé pour le moment') }}</h5>
                <p class="text-muted">{{ __('Revenez bientôt : de nouveaux voyages sont ajoutés régulièrement.') }}</p>
            </div>
        @endif
        <div class="owl-carousel place-slider nav-center">
            @foreach ($voyages as $voyage)
            @php
                $cardImage = $voyage->image_url;
                $defaultCardFallback = \App\Support\BrandImages::url('blasti-hero-bus-bg.png');
                $placesRestantes = max(0, ($voyage->autocar?->nbr_siege ?? 0) - ($voyage->reservations_count ?? 0));
                $duree = $voyage->dureeFormattee();
                $detailUrl = route('client.reservations.show', $voyage->id);
            @endphp
            <div class="place-item mb-4">
                {{-- Image Area with Aspect Ratio & Subtle Overlay --}}
                <div class="place-img position-relative">
                    <a href="{{ $detailUrl }}" class="d-block w-100 h-100 bl-img-link" tabindex="-1" aria-hidden="true">
                        <img src="{{ $cardImage }}"
                             class="img-fluid"
                             alt="{{ __($voyage->villeDepart->ville) }} → {{ __($voyage->villeArrivee->ville) }}"
                             loading="lazy"
                             onerror="this.onerror=null; this.src='{{ $defaultCardFallback }}';">
                    </a>
                    <div class="bl-img-gradient-overlay"></div>
                    <div class="fav-item-overlay">
                        <span class="bl-departure-badge">
                            <i class="isax isax-clock"></i>
                            <span>{{ \Carbon\Carbon::parse($voyage->heure_depart)->format('H:i') }}</span>
                        </span>
                        <a href="javascript:void(0);"
                           class="fav-icon wishlist-toggle {{ in_array($voyage->id, $wishlistIds) ? 'active' : '' }}"
                           data-id="{{ $voyage->id }}"
                           title="{{ __('Favoris') }}"
                           aria-label="{{ __('Ajouter aux favoris') }}">
                            <i class="isax {{ in_array($voyage->id, $wishlistIds) ? 'isax-heart5 text-danger' : 'isax-heart' }}"></i>
                        </a>
                    </div>
                </div>

                {{-- Card Content --}}
                <div class="place-content d-flex flex-column flex-grow-1">
                    {{-- Route Main Headline --}}
                    <h5 class="bl-card-title mb-2">
                        <a href="{{ $detailUrl }}" class="bl-route-link" title="{{ __($voyage->villeDepart->ville) }} → {{ __($voyage->villeArrivee->ville) }}">
                            <span class="bl-city-name">{{ __($voyage->villeDepart->ville) }}</span>
                            <span class="bl-arrow-separator"><i class="isax isax-arrow-right-1"></i></span>
                            <span class="bl-city-name">{{ __($voyage->villeArrivee->ville) }}</span>
                        </a>
                    </h5>

                    {{-- Company and Autocar Pill --}}
                    <div class="bl-card-subtitle d-flex align-items-center justify-content-between mb-2">
                        <span class="bl-company-name text-truncate">
                            <i class="isax isax-bus me-1 text-primary"></i>
                            <span>{{ $voyage->autocar?->societe?->raison_social ?? __('Compagnie partenaire') }}</span>
                        </span>
                        @if ($voyage->autocar?->classe)
                            <span class="badge bl-class-badge">{{ $voyage->autocar->classe }}</span>
                        @elseif ($voyage->typeVoyage)
                            <span class="badge bl-class-badge">{{ $voyage->typeVoyage->type_voyage }}</span>
                        @endif
                    </div>

                    {{-- Timing and Duration Info Box --}}
                    <div class="bl-card-timing mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                            <div class="d-flex align-items-center gap-1 bl-timing-schedule">
                                <i class="isax isax-calendar-2 text-primary"></i>
                                <span class="fw-medium">{{ \Carbon\Carbon::parse($voyage->date_depart)->format('d/m/Y') }}</span>
                                <span class="text-muted mx-1">·</span>
                                <span class="fw-semibold text-dark bl-timing-hours">{{ \Carbon\Carbon::parse($voyage->heure_depart)->format('H:i') }} → {{ \Carbon\Carbon::parse($voyage->heure_arrivee)->format('H:i') }}</span>
                            </div>
                            @if (!empty($duree))
                                <span class="bl-duration-pill">
                                    <i class="isax isax-timer-1"></i>
                                    <span>{{ $duree }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Footer: Price + Smart Seats Badge + CTA Button --}}
                    <div class="bl-card-footer mt-auto pt-3 border-top d-flex align-items-center justify-content-between gap-2">
                        <div class="bl-price-block">
                            <span class="bl-price-label text-muted d-block">{{ __('À partir de') }}</span>
                            <div class="bl-price-val text-primary fw-bold">
                                <span>{{ number_format($voyage->prixActuel(), 0, ',', ' ') }}</span>
                                <span class="bl-currency">{{ __('DHS') }}</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if ($placesRestantes > 5)
                                <span class="badge bl-seat-badge bl-seat-available" title="{{ __(':n places disponibles', ['n' => $placesRestantes]) }}">
                                    <i class="isax isax-tick-circle"></i>
                                    <span>{{ $placesRestantes }} {{ __('places') }}</span>
                                </span>
                            @elseif ($placesRestantes > 0)
                                <span class="badge bl-seat-badge bl-seat-low" title="{{ __(':n places restantes', ['n' => $placesRestantes]) }}">
                                    <i class="isax isax-warning-2"></i>
                                    <span>{{ $placesRestantes }} {{ $placesRestantes > 1 ? __('places') : __('place') }}</span>
                                </span>
                            @else
                                <span class="badge bl-seat-badge bl-seat-full">
                                    {{ __('Complet') }}
                                </span>
                            @endif

                            <a href="{{ $detailUrl }}" class="btn btn-primary btn-sm rounded-pill bl-book-btn">
                                <span>{{ __('Réserver') }}</span>
                                <i class="isax isax-arrow-right-3 ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center view-all wow fadeInUp">
            <a href="{{ route('voyages.list') }}" class="btn btn-dark">{{ __('View All Voyages') }}<i class="isax isax-arrow-right-3 ms-2"></i></a>
        </div>
    </div>
</section>
<!-- /Place Section -->

    <!-- How it works -->
    <section class="section pb-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-10 text-center">
                    <div class="section-header section-header-four text-center">
                        <h2 class="mb-2">{!! __('<span>Comment</span> ça marche ?') !!}</h2>
                        <p class="sub-title">{{ __('Votre billet de bus en trois étapes, sans passer au guichet.') }}</p>
                    </div>
                </div>
            </div>
            <div class="row g-4 mz-steps">
                @foreach ([
                    ['isax-search-normal-1', __('Cherchez votre trajet'), __('Choisissez votre ville de départ, votre destination et la date : nous affichons tous les départs disponibles.')],
                    ['isax-ticket', __('Choisissez votre siège'), __('Voyez les places libres dans l\'autocar et réservez celle qui vous convient, fenêtre ou couloir.')],
                    ['isax-document-download', __('Recevez votre billet'), __('Votre billet est confirmé tout de suite : téléchargez-le en PDF et présentez-le à l\'embarquement.')],
                ] as $i => [$icon, $title, $text])
                    <div class="col-md-4">
                        <div class="mz-step">
                            <span class="mz-step-num">0{{ $i + 1 }}</span>
                            <span class="mz-step-icon"><i class="isax {{ $icon }}"></i></span>
                            <h5>{{ $title }}</h5>
                            <p>{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <!-- /How it works -->

    <!-- Destination Section -->
    @php
        $displayVilles = \App\Models\Ville::has('voyagesDepart')->orHas('voyagesArrivee')
            ->withCount(['voyagesArrivee as departs_count' => fn ($q) => $q->bookable()])
            ->orderByDesc('departs_count')->orderBy('ville')
            ->take(8)->get();
    @endphp
    <section class="section destination-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-10 text-center">
                    <div class="section-header section-header-four text-center">
                        <h2 class="mb-2">{!! __('<span>Nos</span> Destinations') !!}</h2>
                        <p class="sub-title">{{ __('Découvrez toutes les villes desservies par nos voyages. Réservez votre place dès maintenant.') }}</p>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center g-4">
                @foreach ($displayVilles as $ville)
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <a href="{{ route('voyages.client.index', ['ville_arrivee' => $ville->id]) }}" class="location-wrap d-block position-relative">
                            @if ($ville->photo_url)
                                <img src="{{ $ville->photo_url }}" alt="{{ __($ville->ville) }}" loading="lazy" style="height: 250px; object-fit: cover; width: 100%;"
                                     onerror="this.onerror=null; this.src='{{ \App\Support\BrandImages::url('blasti-hero-bus-bg.png') }}';">
                            @else
                                {{-- No photo yet (the admin can add one in Villes): branded tile instead of a wrong picture --}}
                                <div class="d-flex align-items-center justify-content-center text-white fw-bold fs-3"
                                     style="height: 250px; background: radial-gradient(400px 160px at 100% 0%, rgba(var(--accent-rgb),.45), transparent 70%), linear-gradient(135deg, var(--brand-900), var(--brand));">
                                    <i class="isax isax-location" style="font-size: 3rem; opacity: .35; transform: translateY(-38px);"></i>
                                </div>
                            @endif
                            <span class="loc-name bg-white">{{ __($ville->ville) }}</span>
                            @if ($ville->departs_count)
                                <span class="mz-dest-count">{{ $ville->departs_count }} {{ $ville->departs_count > 1 ? __('départs à venir') : __('départ à venir') }}</span>
                            @endif
                            <span class="loc-view"><i class="isax isax-arrow-right-1"></i></span>
                        </a>
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('pages.destinations') }}" class="btn btn-outline-primary rounded-pill px-4">{{ __('Toutes les destinations') }} <i class="isax isax-arrow-right-3 ms-1"></i></a>
            </div>
        </div>
    </section>
    <!-- /Destination Section -->





    <!-- Why us -->
    <section class="section mz-why">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-5">
                    <div class="section-header section-header-four mb-4">
                        <h2 class="mb-2">{!! __('Pourquoi <span>:brand</span> ?', ['brand' => e(config('safar.nom'))]) !!}</h2>
                        <p class="sub-title">{{ __('Les compagnies de bus du Maroc réunies sur un seul site, avec des prix clairs et une réservation en ligne.') }}</p>
                    </div>
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
                <div class="col-lg-7">
                    <div class="row g-4">
                        @foreach ([
                            ['isax-ticket-star', __('Votre siège réservé'), __('Vous choisissez votre place sur le plan de l\'autocar : elle vous est garantie.')],
                            ['isax-tag', __('Prix affichés, sans surprise'), __('Le prix que vous voyez est celui que vous payez, frais compris.')],
                            ['isax-document-text', __('Billet PDF immédiat'), __('Votre billet est disponible dans votre espace et téléchargeable à tout moment.')],
                            ['isax-shield-tick', __('Compagnies identifiées'), __('Nous travaillons avec des sociétés de transport identifiées (ICE, contact, flotte).')],
                            ['isax-clock', __('Horaires à jour'), __('Si un départ change, votre billet est mis à jour automatiquement.')],
                            ['isax-message-question', __('Une équipe qui répond'), __('Une question sur votre voyage ? Écrivez-nous depuis la page contact.')],
                        ] as [$icon, $title, $text])
                            <div class="col-md-6">
                                <div class="mz-feature">
                                    <span class="mz-feature-icon"><i class="isax {{ $icon }}"></i></span>
                                    <div>
                                        <h6>{{ $title }}</h6>
                                        <p>{{ $text }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /Why us -->
    <!-- Client Section -->
    @php
        // partner companies with their next departures (real numbers, same as the Compagnies page)
        $societes = App\Models\Societe::has('autocars')->with('autocars:id,societe_id')->withCount('autocars')->orderBy('raison_social')->get();
        $departsParBus = App\Models\Voyage::bookable()->reorder()->selectRaw('autocar_id, count(*) as n')->groupBy('autocar_id')->pluck('n', 'autocar_id');
        $societes->each(fn ($s) => $s->departs_count = $s->autocars->sum(fn ($a) => $departsParBus[$a->id] ?? 0));
        $societes = $societes->sortByDesc('departs_count')->values();
    @endphp
    @if ($societes->isNotEmpty())
    <section class="section bl-partners">
        <div class="container">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4 wow fadeInUp" data-wow-delay="0.1s">
                <div class="section-header section-header-four mb-0">
                    <h2 class="mb-2">{!! __('Nos <span>compagnies</span> partenaires') !!}</h2>
                    <p class="sub-title mb-0">{{ __('Des sociétés de transport identifiées : choisissez la vôtre et voyez ses prochains départs.') }}</p>
                </div>
                <a href="{{ route('pages.compagnies') }}" class="btn btn-outline-primary rounded-pill px-4">{{ __('Toutes les compagnies') }} <i class="isax isax-arrow-right-3 ms-1"></i></a>
            </div>

            <div class="row g-3">
                @foreach ($societes->take(8) as $societe)
                    <div class="col-xl-3 col-md-4 col-sm-6 wow fadeInUp" data-wow-delay="{{ 0.05 * ($loop->index % 4) }}s">
                        <a href="{{ route('client.societes.showVoyageSociete.index', ['societe' => $societe->id]) }}" class="bl-partner">
                            @if ($societe->logo)
                                <img src="{{ asset('storage/' . $societe->logo) }}" alt="{{ $societe->raison_social }}" class="bl-partner-logo">
                            @else
                                <span class="bl-partner-logo bl-partner-initials">{{ collect(preg_split('/[\s,&-]+/u', $societe->raison_social))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('') }}</span>
                            @endif
                            <span class="bl-partner-body">
                                <span class="bl-partner-name">{{ $societe->raison_social }}</span>
                                <x-rating :societe="$societe->id" />
                                <span class="bl-partner-meta">
                                    @if ($societe->ville)<span><i class="isax isax-location"></i>{{ __($societe->ville) }}</span>@endif
                                    <span><i class="isax isax-bus"></i>{{ trans_choice('{0} aucun départ|{1} :count départ|[2,*] :count départs', $societe->departs_count, ['count' => $societe->departs_count]) }}</span>
                                </span>
                            </span>
                            <i class="isax isax-arrow-right-3 bl-partner-go"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- /Client Section -->

    <!-- Call to action -->
    <section class="section pt-0 bl-cta-wrap">
        <div class="container">
            <div class="bl-cta">
                <div>
                    <h3>{{ __('Prêt à partir ?') }}</h3>
                    <p>{{ __('Choisissez votre trajet et réservez votre siège en quelques minutes.') }}</p>
                </div>
                <a href="#search-form" class="btn btn-light btn-lg rounded-pill px-4" data-bl-to-search>{{ __('Chercher un bus') }} <i class="isax isax-search-normal-1 ms-1"></i></a>
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="{{ asset('assets/js/blasti-hero.js') . '?v=' . @filemtime(public_path('assets/js/blasti-hero.js')) }}"></script>
        <script>
            // "Chercher un bus": back up to the search card, below the sticky header
            document.querySelectorAll('[data-bl-to-search]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var card = document.querySelector('.banner-search-four');
                    window.scrollTo({ top: card.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
                });
            });
        </script>
    @endpush
</x-app-layout>
