@if (! empty($autresDates))
    <div class="alert alert-info d-flex align-items-center gap-2 mt-3"><i class="isax isax-calendar-1"></i>{{ __('Aucun départ le :date sur ce trajet : voici les prochains départs.', ['date' => \Carbon\Carbon::parse($date)->translatedFormat('d F')]) }}</div>
@endif
@if($voyages->isEmpty())
    <div class="no-results-found text-center py-5">
        <div class="mb-4">
            <i class="isax isax-search-status text-primary" style="font-size: 4rem;"></i>
        </div>
        <h3 class="fw-medium mb-2">{{ __('Aucun voyage trouvé') }}</h3>
        <p class="text-muted mb-4">{{ __('Désolé, nous n\'avons trouvé aucun voyage correspondant à vos critères de recherche.') }}</p>
        <div class="suggestions">
            <h6 class="fw-medium mb-3">{{ __('Suggestions :') }}</h6>
            <ul class="list-unstyled text-muted">
                <li><i class="fas fa-check-circle text-primary me-2"></i>{{ __('Vérifiez les dates de voyage') }}</li>
                <li><i class="fas fa-check-circle text-primary me-2"></i>{{ __('Essayez d\'autres villes de départ ou d\'arrivée') }}</li>
                <li><i class="fas fa-check-circle text-primary me-2"></i>{{ __('Élargissez votre période de recherche') }}</li>
            </ul>
        </div>
    </div>
@else
    @php
        // Voyages in the wishlist, loaded once for the whole list (not 2 queries per card)
        $wishlistIds = Auth::check() ? Auth::user()->wishlists()->pluck('voyage_id')->all() : [];
    @endphp
    @foreach ($voyages as $voyage)
        @php
            // the client's part of the trip (search cities), or the whole route
            [$segA, $segB] = $voyage->segmentFor($from ?? null, $to ?? null) ?? $voyage->segmentFor();
            $via = $voyage->arrets->filter(fn ($s) => $s->ordre > $segA->ordre && $s->ordre < $segB->ordre);
            $bookUrl = route('client.reservations.show', ['voyage' => $voyage->id, 'de' => $segA->id, 'a' => $segB->id]);
            // voyage photo > bus photo > destination city photo > branded tile (never a wrong city)
            $voyageImage = $voyage->image_url;
        @endphp
        <div class="card voyage-card mb-4 border-0 shadow-sm overflow-hidden">
            <div class="row g-0 h-100">
                <div class="col-md-4 col-sm-5">
                    <div class="voyage-img-wrapper h-100 position-relative">
                        <a href="{{ $bookUrl }}" class="d-block h-100">
                            @if ($voyageImage)
                                <img src="{{ $voyageImage }}" class="img-fluid h-100 w-100 object-fit-cover" alt="{{ __($voyage->villeArrivee?->ville ?? '') }}" loading="lazy" onerror="this.onerror=null; this.src='{{ \App\Support\BrandImages::url('blasti-hero-bus-bg.png') }}';">
                            @else
                                <div class="h-100 w-100 d-flex flex-column align-items-center justify-content-center text-white" style="min-height: 170px; background: radial-gradient(300px 120px at 100% 0%, rgba(var(--accent-rgb),.45), transparent 70%), linear-gradient(135deg, var(--brand-900), var(--brand));">
                                    <i class="isax isax-bus" style="font-size: 2.2rem; opacity: .8;"></i>
                                    <span class="fw-semibold mt-1">{{ __($voyage->villeArrivee?->ville ?? '') }}</span>
                                </div>
                            @endif
                        </a>
                        <div class="position-absolute top-0 end-0 p-3">
                            <a href="javascript:void(0);" class="fav-icon wishlist-toggle {{ in_array($voyage->id, $wishlistIds) ? 'active' : '' }}" data-id="{{ $voyage->id }}">
                                <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    <i class="isax {{ in_array($voyage->id, $wishlistIds) ? 'isax-heart5 text-danger' : 'isax-heart' }}"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-sm-7">
                    <div class="card-body p-4 d-flex flex-column h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title fw-bold text-dark mb-1">{{ $voyage->autocar->societe->raison_social }} <x-rating :societe="$voyage->autocar->societe_id" class="ms-1 align-middle" /></h5>
                                <div class="text-muted fs-12">{{ __('Matricule : :matricule', ['matricule' => $voyage->autocar->matricule]) }}</div>
                            </div>
                            <div class="text-end">
                                @php $placesRestantes = $voyage->seatsLeft($segA, $segB); @endphp
                                @if ($placesRestantes > 0)
                                    <div class="badge bg-outline-success rounded-pill px-3 py-2 fs-12">
                                        {{ $placesRestantes > 1 ? __(':n places restantes', ['n' => $placesRestantes]) : __(':n place restante', ['n' => $placesRestantes]) }}
                                    </div>
                                @else
                                    <div class="badge bg-danger rounded-pill px-3 py-2 fs-12">{{ __('Complet') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="voyage-details mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="isax isax-location text-primary me-2"></i>
                                <span class="fw-medium">{{ __($segA->ville->ville) }}</span>
                                <i class="isax isax-arrow-right-3 mx-2 text-primary"></i>
                                <span class="fw-medium">{{ __($segB->ville->ville) }}</span>
                            </div>
                            @if ($via->isNotEmpty())
                                <div class="mz-via mb-2"><i class="isax isax-routing-2 me-1"></i>{{ __('Via :villes', ['villes' => $via->map(fn ($s) => __($s->ville->ville))->join(', ')]) }}</div>
                            @endif
                            @if ($segA->ordre > 0 || $segB->ordre < $voyage->arrets->max('ordre'))
                                <div class="mz-via mb-2">{{ __('Bus :de → :a', ['de' => __($voyage->villeDepart->ville), 'a' => __($voyage->villeArrivee->ville)]) }}</div>
                            @endif
                            @php
                                $dep = $segA->passage_at;
                                $arr = $segB->passage_at;
                                $mins = max(0, $dep->diffInMinutes($arr));
                            @endphp
                            <div class="text-muted fs-14 mb-2">
                                <i class="isax isax-calendar me-1"></i> {{ ucfirst($dep->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY')) }}
                            </div>
                            <div class="mz-times">
                                <strong>{{ $dep->format('H:i') }}</strong>
                                <span class="mz-duration"><span>{{ intdiv($mins, 60) }} h {{ str_pad($mins % 60, 2, '0', STR_PAD_LEFT) }}</span></span>
                                <strong>{{ $arr->format('H:i') }}@if (! $arr->isSameDay($dep))<sup class="text-muted fs-10 ms-1">+1</sup>@endif</strong>
                            </div>
                        </div>

                        <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top">
                            <div>
                                <span class="text-muted fs-14">{{ __('Prix par place') }}</span>
                                <h4 class="text-primary fw-bold mb-0">{{ number_format($voyage->segmentPrice($segA, $segB), 2, ',', ' ') }} {{ __('DH') }}</h4>
                            </div>
                            <a href="{{ $bookUrl }}" class="btn btn-primary rounded-pill px-4 shadow-sm hover-lift">
                                {{ __('Réserver') }} <i class="isax isax-arrow-right-3 ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
