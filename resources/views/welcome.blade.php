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
                            <h1 class="text-white display-4 mb-2">{!! __('Découvrez de nouveaux horizons, un :icon trajet à la fois avec Blasti !', ['icon' => '<span class="flight-icon"><img src="'.\App\Support\BrandImages::url('blasti-hero-bus.png').'" class="blasti-hero-bus" style="height: 66px; width: auto; vertical-align: middle;" alt="icon"></span>']) !!}</h1>
                            <p class="text-white mx-auto">{{ __('Votre plateforme idéale pour organiser et vivre des voyages inoubliables.') }}</p>
                            {{-- <a class="video-btn video-effect" data-fancybox="" href="https://youtu.be/NSAOrGb9orM"><i class="isax isax-play5"></i></a> --}}
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
                {{-- <div class="card-header">
                <ul class="nav">
                    <li>
                        <a href="javascript:void(0);" class="nav-link active" data-bs-toggle="tab" data-bs-target="#flight">
                            <i class="isax isax-airplane5 me-2"></i>Flights
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="nav-link" data-bs-toggle="tab" data-bs-target="#Hotels">
                            <i class="isax isax-buildings5 me-2"></i>Hotels
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="nav-link" data-bs-toggle="tab" data-bs-target="#Cars">
                            <i class="isax isax-car5 me-2"></i>Cars
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="nav-link" data-bs-toggle="tab" data-bs-target="#Cruise">
                            <i class="isax isax-ship5 me-2"></i>Cruise
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="nav-link" data-bs-toggle="tab" data-bs-target="#Tour">
                            <i class="isax isax-camera5 me-2"></i>Tour
                        </a>
                    </li>
                </ul>
            </div> --}}
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
        </div>
    </section>
    <!-- /Banner Search -->

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
            ->get();
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
                                <img src="{{ $ville->photo_url }}" alt="{{ __($ville->ville) }}" loading="lazy" style="height: 250px; object-fit: cover; width: 100%;">
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
        </div>
    </section>
    <!-- /Destination Section -->



 <!-- Place Section -->
<section class="section place-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-10 text-center wow fadeInUp" data-wow-delay="0.2s">
                <div class="section-header section-header-four mb-4 text-center">
                    <h2 class="mb-2">{!! __('<span>Expériences</span> de Voyage Uniques.') !!}</h2>
                    <p class="sub-title">{{ __('Connectez-vous aux meilleures offres de transport professionnel au Maroc — Réservez votre prochain trajet en toute simplicité.') }}</p>
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
            <div class="place-item mb-4 position-relative">
                <div class="place-img">
                    <a href="{{ route('client.reservations.show', $voyage->id) }}">
                        <img src="{{ $voyage->image ? asset('storage/' . $voyage->image) : ($voyage->autocar?->image ? asset('storage/' . $voyage->autocar->image) : ($voyage->villeArrivee?->photo_url ?? \App\Support\BrandImages::url('blasti-hero-bus-bg.png'))) }}" class="img-fluid" alt="{{ __($voyage->villeDepart->ville) }} → {{ __($voyage->villeArrivee->ville) }}">
                    </a>
                    <div class="fav-item-overlay">
                        <a href="javascript:void(0);"
                           class="fav-icon wishlist-toggle {{ in_array($voyage->id, $wishlistIds) ? 'active' : '' }}"
                           data-id="{{ $voyage->id }}">
                            <i class="isax {{ in_array($voyage->id, $wishlistIds) ? 'isax-heart5 text-danger' : 'isax-heart' }}"></i>
                        </a>
                        <span class="badge badge-warning badge-xs text-gray-9 fs-13 fw-medium rounded"><i class="isax isax-clock me-1"></i>{{ \Carbon\Carbon::parse($voyage->heure_depart)->format('H:i') }}</span>
                    </div>
                </div>
                <div class="place-content">
                    <div class="flight-loc d-flex align-items-center justify-content-between mb-2">
                        <span class="loc-name d-inline-flex align-items-center">
                            <i class="isax isax-bus me-2"></i>{{ __($voyage->villeDepart->ville) }}
                        </span>
                        <span class="arrow-icon"><i class="isax isax-arrow-2"></i></span>
                        <span class="loc-name d-inline-flex align-items-center">
                            <i class="isax isax-location me-2"></i>{{ __($voyage->villeArrivee->ville) }}
                        </span>
                    </div>
                    <h5 class="text-truncate mb-1">
                        <a href="{{ route('client.reservations.show', $voyage->id) }}" class="stretched-link">{{ $voyage->autocar->societe->raison_social }}</a>
                    </h5>
                    <div class="date-info p-2 mb-3">
                        <p class="d-flex align-items-center">
                            <i class="isax isax-calendar-2 me-2"></i>{{ \Carbon\Carbon::parse($voyage->date_depart)->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($voyage->heure_depart)->format('H:i') }} → {{ \Carbon\Carbon::parse($voyage->heure_arrivee)->format('H:i') }}
                        </p>
                    </div>
                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                        <h6 class="text-primary"><span class="fs-14 fw-normal text-default">{{ __('À partir de ') }}</span>{{ number_format($voyage->prix, 0, ',', ' ') }} {{ __('DHS') }}</h6>
                        <div class="d-flex align-items-center">
                            @php $placesRestantes = max(0, $voyage->autocar->nbr_siege - $voyage->reservations_count); @endphp
                            @if ($placesRestantes > 0)
                                <span class="badge bg-outline-success fs-10 fw-medium me-2">{{ $placesRestantes }} {{ __('Places restantes') }}</span>
                            @else
                                <span class="badge bg-danger fs-10 fw-medium me-2">{{ __('Complet') }}</span>
                            @endif
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

    <!-- Why BLASTI -->
    <section class="section mz-why">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <div class="section-header section-header-four mb-4">
                        <h2 class="mb-2">{!! __('Pourquoi <span>BLASTI</span> ?') !!}</h2>
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
    <!-- /Why BLASTI -->
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


<style>
    .place-item {
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #eee;
    }
    .place-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .place-img {
        height: 220px;
        position: relative;
        overflow: hidden;
        background: #f8f9fa;
    }
    .place-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .place-item:hover .place-img img {
        transform: scale(1.1);
    }
    .fav-item-overlay {
        position: absolute;
        top: 15px;
        left: 0;
        right: 0;
        padding: 0 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 10;
        pointer-events: none;
    }
    .fav-item-overlay > * {
        pointer-events: auto;
    }
    .fav-icon {
        background: white;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #333;
        box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        transition: all 0.2s ease;
    }
    .fav-icon:hover {
        transform: scale(1.1);
        color: #ff4d4f;
    }
    .fav-icon i {
        font-size: 18px;
    }
    .place-content {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .date-info {
        background: #f0f7ff;
        border-radius: 8px;
        color: #006ce4;
    }
</style>

    @push('scripts')
        <script src="{{ asset('assets/js/blasti-hero.js') . '?v=' . @filemtime(public_path('assets/js/blasti-hero.js')) }}"></script>
    @endpush
</x-app-layout>
