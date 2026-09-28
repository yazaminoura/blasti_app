<footer class="footer-four">
    <div class="footer-top">
        <div class="container">
            <div class="row">
                <div class="col-xl-7">
                    <div class="row row-cols-md-4 row-cols-sm-2 row-cols-1">
                        <div class="col">
                            <div class="footer-widget">
                                <h5>{{ __('Navigation') }}</h5>
                                <ul class="footer-menu">
                                    <li>
                                        <a href="{{ route('home') }}">{{ __('Accueil') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('voyages.list') }}">{{ __('Nos Voyages') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('pages.destinations') }}">{{ __('Destinations') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('pages.compagnies') }}">{{ __('Compagnies') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('pages.apropos') }}">{{ __('À propos') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('contact') }}">{{ __('Contactez-nous') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col">
                            <div class="footer-widget">
                                <h5>{{ __('Mon Compte') }}</h5>
                                <ul class="footer-menu">
                                    <li>
                                        <a href="{{ route('client.profile.dashboard.index') }}">{{ __('Mon espace') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('client.profile.reservations.index') }}">{{ __('Mes réservations') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('client.profile.parametres.index') }}">{{ __('Paramètres') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('wishlist') }}">{{ __('Mes favoris') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col">
                            <div class="footer-widget">
                                <h5>{{ __('Assistance') }}</h5>
                                <ul class="footer-menu">
                                    <li>
                                        <a href="{{ route('pages.aide') }}">{{ __('Centre d\'aide') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('contact') }}">{{ __('Support Client') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('pages.aide') }}">{{ __('Questions fréquentes') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('voyages.list') }}">{{ __('Réserver un billet') }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 col-lg-6">
                    <div class="footer-widget">
                        {{-- Brand block (replaces a newsletter form that sent nothing) --}}
                        <div class="mb-4 mz-footer-brand d-flex gap-3 align-items-start">
                            <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" alt="BLASTI" class="flex-shrink-0 mz-logo-light">
                            <img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" alt="BLASTI" class="flex-shrink-0 mz-logo-dark">
                            <div>
                                <p class="mb-2 fs-14">{{ __('Réservez vos billets de bus entre les villes du Maroc, choisissez votre siège et recevez votre billet en PDF.') }}</p>
                                <a href="{{ route('voyages.list') }}" class="btn btn-primary btn-sm">{{ __('Voir les départs') }}</a>
                            </div>
                        </div>
                        <h5 class="mb-0">{{ __('Nous contacter') }}</h5>
                        <div class="d-sm-flex align-items-center justify-content-center justify-content-between">
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start me-3 mt-2">
                                <span class="avatar avatar-lg bg-light rounded-circle flex-shrink-0">
                                    <i class="ti ti-headphones-filled fs-24 text-gray-9"></i>
                                </span>
                                <div class="ms-2">
                                    <p class="fs-14 mb-1">{{ __('Service client') }}</p>
                                    <h6 class="fw-medium"><a href="tel:{{ preg_replace('/\s+/', '', config('safar.contact.telephone')) }}">{{ config('safar.contact.telephone') }}</a></h6>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-center justify-content-sm-start mt-2">
                                <span class="avatar avatar-lg bg-light rounded-circle flex-shrink-0">
                                    <i class="ti ti-message-2 fs-24 text-gray-9"></i>
                                </span>
                                <div class="ms-2">
                                    <p class="fs-14 mb-1">{{ __('Écrivez-nous') }}</p>
                                    <h6 class="fw-medium text-dark"><a href="mailto:{{ config('safar.contact.email') }}">{{ config('safar.contact.email') }}</a></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <p>{{ __('Tous droits réservés') }}, <a href="{{ route('home') }}" class="text-primary fw-medium">BLASTI</a> {{ date('Y') }}</p>
                        @php $social = array_filter(config('safar.social', [])); @endphp
                        @if ($social)
                            <div class="d-flex align-items-center">
                                <ul class="social-icon">
                                    @foreach ($social as $network => $url)
                                        <li><a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"><i class="fa-brands fa-{{ $network === 'x' ? 'x-twitter' : $network }}"></i></a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <ul class="policy-links">
                            <li><a href="{{ route('pages.aide') }}">{{ __('FAQ') }}</a></li>
                            <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Footer Bottom -->

</footer>

