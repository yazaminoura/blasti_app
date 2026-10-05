@php
    $currentLocale = app()->getLocale();
    // name = short code on the button, label = the language written in itself (in the list)
    $locales = [
        'fr' => ['flag' => 'france-flag.svg', 'name' => 'FR', 'label' => 'Français'],
        'ar' => ['flag' => 'morocco-flag.svg', 'name' => 'AR', 'label' => 'العربية'],
        'en' => ['flag' => 'us-flag.svg', 'name' => 'EN', 'label' => 'English'],
    ];
    $activeLocale = $locales[$currentLocale] ?? $locales['fr'];
@endphp

<div class="main-header main-header-four">
    <!-- Header Topbar-->
    <!-- /Header Topbar-->

    <!-- Header -->
    <header class="header-four">
        <div class="container">
            <div class="offcanvas-info">
                <div class="offcanvas-wrap">
                    <div class="offcanvas-detail">
                        <div class="offcanvas-head">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                @if (\App\Support\BrandImages::hasCustomLogo())
                                    <a href="{{ route('home') }}" class="black-logo-responsive">
                                        <img src="{{ \App\Support\BrandImages::logoUrl() }}" alt="{{ config('safar.nom') }}" style="height: 48px; width: auto; max-width: 160px; object-fit: contain;">
                                    </a>
                                    <a href="{{ route('home') }}" class="white-logo-responsive">
                                        <img src="{{ \App\Support\BrandImages::logoDarkUrl() }}" alt="{{ config('safar.nom') }}" style="height: 48px; width: auto; max-width: 160px; object-fit: contain;">
                                    </a>
                                @elseif (! \App\Support\BrandImages::isDefaultBrand())
                                    <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-decoration-none py-1">
                                        <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" alt="{{ config('safar.nom') }}" style="height: 38px; width: auto;">
                                        <span class="fs-4 fw-bold text-dark">{{ config('safar.nom') }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('home') }}" class="black-logo-responsive">
                                        <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" alt="Blasti" style="height: 48px; width: auto;">
                                    </a>
                                    <a href="{{ route('home') }}" class="white-logo-responsive">
                                        <img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" alt="Blasti" style="height: 48px; width: auto;">
                                    </a>
                                @endif
                                <div class="offcanvas-close">
                                    <i class="fa-solid fa-xmark"></i>
                                </div>
                            </div>
                            <div class="wishlist-info d-flex justify-content-between align-items-center">
                                <h6 class="fs-16 fw-medium">{{ __('Favoris') }}</h6>
                                <div class="d-flex align-items-center">
                                    <div class="fav-dropdown">
                                        <a href="{{ route('wishlist') }}" class="position-relative">
                                            <i class="isax isax-heart"></i><span class="count-icon bg-secondary text-white wishlist-count">{{ Auth::check() ? Auth::user()->wishlists()->count() : 0 }}</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mobile-menu fix mb-3"></div>
                        <div class="offcanvas__contact">
                            <div class="mt-4">
                                <div class="header-dropdown d-flex flex-fill">
                                    <div class="w-100">
                                        <div class="dropdown flag-dropdown mb-2">
                                            <a href="javascript:void(0);" class="dropdown-toggle bg-white border d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                                                <img src="{{ asset('assets/img/flags/' . $activeLocale['flag']) }}" class="me-2" alt="" style="width: 20px;">{{ $activeLocale['name'] }}
                                            </a>
                                            <ul class="dropdown-menu p-2 bl-lang-menu">
                                                @foreach($locales as $code => $info)
                                                    <li>
                                                        <a class="dropdown-item rounded d-flex align-items-center bl-lang-item {{ $code === $currentLocale ? 'active' : '' }}" href="{{ route('lang.switch', $code) }}" lang="{{ $code }}">
                                                            <img src="{{ asset('assets/img/flags/' . $info['flag']) }}" class="me-2" alt="" style="width: 20px;"><span>{{ $info['label'] }}</span>@if ($code === $currentLocale)<i class="isax isax-tick-circle5 ms-auto ps-3"></i>@endif
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @auth
                                    <div class="dropdown profile-dropdown w-100 mb-3">
                                        <a href="javascript:void(0);" class="dropdown-toggle bg-white border d-flex align-items-center p-2 rounded w-100" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="avatar avatar-sm me-2">
                                                <img src="{{ auth()->user()->profile_image_path }}"
                                                     alt="{{ __('image') }}" class="img-fluid rounded-circle"
                                                     onerror="this.src='{{ asset('assets/img/users/user-01.jpg') }}'"
                                                     style="width: 30px; height: 30px; object-fit: cover;">
                                            </span>
                                            <span class="fw-medium text-dark">{{ auth()->user()->name }}</span>
                                        </a>
                                        <ul class="dropdown-menu p-2 w-100">
                                            <li>
                                                <a href="{{route('client.profile.dashboard.index')}}" class="dropdown-item rounded p-2">{{ __('Home') }}</a>
                                            </li>
                                            <li>
                                                <a href="{{route('client.profile.reservations.index')}}" class="dropdown-item rounded p-2">{{ __('Mes reservations') }}</a>
                                            </li>
                                            <li>
                                                <a href="{{ route('client.profile.monprofile.index') }}" class="dropdown-item rounded p-2">{{ __('Mon profil') }}</a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider my-2">
                                            </li>
                                            <li>
                                                <a href="{{ route('client.profile.parametres.index') }}" class="dropdown-item rounded p-2">{{ __('Paramètres') }}</a>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item rounded p-2 border-0 bg-transparent text-danger w-100 text-start">{{ __('Déconnexion') }}</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                @else
                                    <div class="btn btn-dark w-100 mb-3">
                                        <a href="{{ route('login') }}" class="text-white">{{ __('Log in') }}</a> /
                                        <a href="{{ route('register') }}" class="text-white">{{ __('Register') }}</a>
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="offcanvas-overlay"></div>
            <div class="header-nav">
                <div class="main-menu-wrapper">
                    <div class="navbar-logo">
                        @if (\App\Support\BrandImages::hasCustomLogo())
                            <a class="logo-white header-logo" href="{{ route('home') }}">
                                <img src="{{ \App\Support\BrandImages::logoUrl() }}" style="height: 48px; width: auto; max-width: 180px; object-fit: contain;" class="logo" alt="{{ config('safar.nom') }}">
                            </a>
                            <a class="logo-dark header-logo" href="{{ route('home') }}">
                                <img src="{{ \App\Support\BrandImages::logoDarkUrl() }}" style="height: 48px; width: auto; max-width: 180px; object-fit: contain;" class="logo" alt="{{ config('safar.nom') }}">
                            </a>
                        @elseif (! \App\Support\BrandImages::isDefaultBrand())
                            <a class="header-logo d-flex align-items-center gap-2 text-decoration-none py-1" href="{{ route('home') }}">
                                <img src="{{ \App\Support\BrandImages::url('blasti-bus.png') }}" style="height: 42px; width: auto;" alt="{{ config('safar.nom') }}">
                                <span class="fs-3 fw-bold text-dark">{{ config('safar.nom') }}</span>
                            </a>
                        @else
                            <a class="logo-white header-logo" href="{{ route('home') }}">
                                <img src="{{ \App\Support\BrandImages::url('blasti-logo.png') }}" style="height: 58px; width: auto;" class="logo" alt="Blasti">
                            </a>
                            <a class="logo-dark header-logo" href="{{ route('home') }}">
                                <img src="{{ \App\Support\BrandImages::url('blasti-logo-dark.png') }}" style="height: 58px; width: auto;" class="logo" alt="Blasti">
                            </a>
                        @endif
                    </div>
                    <nav id="mobile-menu">
                        <ul class="main-nav">
                            <li class="{{ request()->routeIs('home') ? 'active' : '' }}">
                                <a href="{{ route('home') }}">{{ __('Home') }}</a>
                            </li>
                            <li class="{{ request()->routeIs('voyages.*') ? 'active' : '' }}">
                                <a href="{{ route('voyages.list') }}">{{ __('Voyage') }}</a>
                            </li>
                            <li class="{{ request()->routeIs('pages.destinations') ? 'active' : '' }}">
                                <a href="{{ route('pages.destinations') }}">{{ __('Destinations') }}</a>
                            </li>
                            <li class="{{ request()->routeIs('pages.compagnies') ? 'active' : '' }}">
                                <a href="{{ route('pages.compagnies') }}">{{ __('Compagnies') }}</a>
                            </li>
                            <li class="{{ request()->routeIs('pages.aide') ? 'active' : '' }}">
                                <a href="{{ route('pages.aide') }}">{{ __('Aide') }}</a>
                            </li>
                            <li class="{{ request()->routeIs('contact') ? 'active' : '' }}">
                                <a href="{{ route('contact') }}">{{ __('Contact') }}</a>
                            </li>
                            <!-- <li class="{{ request()->routeIs('wishlist') ? 'active' : '' }}">
                                <a href="{{ route('wishlist') }}">{{ __('Wishlist') }}</a>
                            </li> -->
                        </ul>
                    </nav>
                    @if (Route::has('login'))
                        @auth
                            {{-- ========= Profile ========= --}}

                            <div class="header-btn d-flex align-items-center">
                                <div class="me-3 d-flex align-items-center">
                                    <a href="javascript:void(0);" id="dark-mode-toggle" class="theme-toggle">
                                        <i class="isax isax-moon"></i>
                                    </a>
                                    <a href="javascript:void(0);" id="light-mode-toggle" class="theme-toggle">
                                        <i class="isax isax-sun-1"></i>
                                    </a>
                                </div>
                                <div class="fav-dropdown me-3">
                                    <a href="{{ route('wishlist') }}" class="position-relative">
                                        <i class="isax isax-heart"></i><span class="count-icon bg-secondary text-white wishlist-count">{{ Auth::check() ? Auth::user()->wishlists()->count() : 0 }}</span>
                                    </a>
                                </div>
                                <div class="dropdown flag-dropdown me-3 d-none d-xl-block">
                                    <a href="javascript:void(0);" class="dropdown-toggle bg-white border d-flex align-items-center p-2 rounded" data-bs-toggle="dropdown" aria-expanded="false">
                                        <img src="{{ asset('assets/img/flags/' . $activeLocale['flag']) }}" class="me-2" alt="" style="width: 20px;">{{ $activeLocale['name'] }}
                                    </a>
                                    <ul class="dropdown-menu p-2 bl-lang-menu">
                                        @foreach($locales as $code => $info)
                                            <li>
                                                <a class="dropdown-item rounded d-flex align-items-center bl-lang-item {{ $code === $currentLocale ? 'active' : '' }}" href="{{ route('lang.switch', $code) }}" lang="{{ $code }}">
                                                    <img src="{{ asset('assets/img/flags/' . $info['flag']) }}" class="me-2" alt="" style="width: 20px;"><span>{{ $info['label'] }}</span>@if ($code === $currentLocale)<i class="isax isax-tick-circle5 ms-auto ps-3"></i>@endif
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="dropdown profile-dropdown">
                                    <a href="javascript:void(0);" class="d-flex align-items-center" data-bs-toggle="dropdown">
                                        <span class="avatar avatar-md">
                                        <img src="{{ auth()->user()->profile_image_path }}"
                                         alt="{{ __('image') }}"  class="img-fluid rounded-circle border border-white border-4"
                                         onerror="this.src='{{ asset('assets/img/users/user-01.jpg') }}'">
                                        </span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end p-3">
                                        <li>
                                            <a href="{{route('client.profile.dashboard.index')}}" class="dropdown-item d-inline-flex align-items-center rounded fw-medium p-2">{{ __('Home') }}</a>
                                        </li>
                                        <li>
                                            <a href="{{route('client.profile.reservations.index')}}" class="dropdown-item d-inline-flex align-items-center rounded fw-medium p-2">{{ __('Mes reservations') }}</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('client.profile.monprofile.index') }}" class="dropdown-item d-inline-flex align-items-center rounded fw-medium p-2">{{ __('Mon profil') }}</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider my-2">
                                        </li>
                                        <li>
                                            <a href="{{ route('client.profile.parametres.index') }}" class="dropdown-item d-inline-flex align-items-center rounded fw-medium p-2">{{ __('Paramètres') }}</a>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item d-inline-flex align-items-center rounded fw-medium p-2">{{ __('Déconnexion') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        @else
                            <div class="header-btn d-flex align-items-center">
                                <div class="me-3">
                                    <a href="javascript:void(0);" id="dark-mode-toggle" class="theme-toggle">
                                        <i class="isax isax-moon"></i>
                                    </a>
                                    <a href="javascript:void(0);" id="light-mode-toggle" class="theme-toggle">
                                        <i class="isax isax-sun-1"></i>
                                    </a>
                                </div>
                                <div class="fav-dropdown me-3">
                                    <a href="{{ route('wishlist') }}" class="position-relative">
                                        <i class="isax isax-heart"></i><span class="count-icon bg-secondary text-white wishlist-count">{{ Auth::check() ? Auth::user()->wishlists()->count() : 0 }}</span>
                                    </a>
                                </div>
                                <div class="dropdown flag-dropdown me-3 d-none d-xl-block">
                                    <a href="javascript:void(0);" class="dropdown-toggle bg-white border d-flex align-items-center p-2 rounded" data-bs-toggle="dropdown" aria-expanded="false">
                                        <img src="{{ asset('assets/img/flags/' . $activeLocale['flag']) }}" class="me-2" alt="" style="width: 20px;">{{ $activeLocale['name'] }}
                                    </a>
                                    <ul class="dropdown-menu p-2 bl-lang-menu">
                                        @foreach($locales as $code => $info)
                                            <li>
                                                <a class="dropdown-item rounded d-flex align-items-center bl-lang-item {{ $code === $currentLocale ? 'active' : '' }}" href="{{ route('lang.switch', $code) }}" lang="{{ $code }}">
                                                    <img src="{{ asset('assets/img/flags/' . $info['flag']) }}" class="me-2" alt="" style="width: 20px;"><span>{{ $info['label'] }}</span>@if ($code === $currentLocale)<i class="isax isax-tick-circle5 ms-auto ps-3"></i>@endif
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <a href="{{ route('login') }}" class="btn btn-dark d-inline-flex align-items-center me-3"><i class="isax isax-lock me-2"></i>{{ __('Log in') }}</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-dark d-inline-flex align-items-center me-0"><i class="isax isax-lock me-2"></i>{{ __('Register') }}</a>
                                @endif
                        @endauth
                                <div class="header__hamburger d-xl-none my-auto">
                                    <div class="sidebar-menu">
                                        <i class="isax isax-menu5"></i>
                                    </div>
                                </div>
                            </div>
                    @endif
                </div>
            </div>
        </div>
    </header>
    <!-- /Header -->
</div>
