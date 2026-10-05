@php
    $me = auth()->user();
    $roleLabel = $me->isSuperAdmin() ? __('Super administrateur') : ($me->roles->first()?->name ? __($me->roles->first()->name) : __('Administrateur'));
    $currentLocale = app()->getLocale();
    $locales = [
        'fr' => ['flag' => 'france-flag.svg', 'name' => 'FR', 'label' => 'Français'],
        'ar' => ['flag' => 'morocco-flag.svg', 'name' => 'AR', 'label' => 'العربية'],
        'en' => ['flag' => 'us-flag.svg', 'name' => 'EN', 'label' => 'English'],
    ];
    $activeLocale = $locales[$currentLocale] ?? $locales['fr'];
@endphp
<header class="sa-topbar">
    <button type="button" class="sa-icon-btn d-lg-none" data-sa-sidebar-open aria-label="{{ __('Ouvrir le menu') }}">
        <i class="bi bi-list"></i>
    </button>

    <nav class="sa-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
        <a href="{{ route('admin') }}">{{ __('Admin') }}</a>
        <i class="bi bi-chevron-right"></i>
        <span>@yield('title', __('Administration'))</span>
    </nav>

    <div class="sa-topbar-actions">
        {{-- Sélecteur de langue --}}
        <div class="dropdown">
            <button type="button" class="sa-user-btn" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Langue') }}">
                <img src="{{ asset('assets/img/flags/' . $activeLocale['flag']) }}" alt="" style="width: 18px; height: 12px; object-fit: cover; border-radius: 2px;">
                <span class="d-none d-sm-inline fw-semibold small">{{ $activeLocale['name'] }}</span>
                <i class="bi bi-chevron-down small text-muted"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end sa-dropdown" style="min-width: 160px;">
                @foreach ($locales as $code => $info)
                    <a href="{{ route('lang.switch', $code) }}" class="dropdown-item {{ $code === $currentLocale ? 'active' : '' }}" lang="{{ $code }}">
                        <img src="{{ asset('assets/img/flags/' . $info['flag']) }}" alt="" style="width: 18px; height: 12px; object-fit: cover; border-radius: 2px;">
                        <span>{{ $info['label'] }}</span>
                        @if ($code === $currentLocale)
                            <i class="bi bi-check2 ms-auto text-primary"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Light / dark / system, remembered in this browser --}}
        <div class="sa-theme-switch" role="group" aria-label="{{ __('Thème') }}">
            <button type="button" data-sa-theme="light" title="{{ __('Clair') }}"><i class="bi bi-sun"></i></button>
            <button type="button" data-sa-theme="dark" title="{{ __('Sombre') }}"><i class="bi bi-moon-stars"></i></button>
            <button type="button" data-sa-theme="auto" title="{{ __('Système') }}"><i class="bi bi-circle-half"></i></button>
        </div>

        <div class="dropdown">
            <button type="button" class="sa-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ $me->profile_image_path }}" alt="" class="sa-avatar-img">
                <span class="d-none d-md-flex flex-column text-start lh-sm">
                    <strong>{{ $me->name }}</strong>
                    <small>{{ $roleLabel }}</small>
                </span>
                <i class="bi bi-chevron-down small d-none d-md-inline"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end sa-dropdown">
                <div class="px-3 py-2">
                    <div class="fw-semibold">{{ $me->name }}</div>
                    <div class="small text-body-secondary">{{ $me->email }}</div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="{{ route('profile.edit') }}" class="dropdown-item"><i class="bi bi-person"></i> {{ __('Mon profil') }}</a>
                @if ($me->isSuperAdmin())
                    <a href="{{ route('admin.apparence.edit') }}" class="dropdown-item"><i class="bi bi-palette"></i> {{ __('Apparence') }}</a>
                @endif
                <a href="{{ route('home') }}" class="dropdown-item" target="_blank"><i class="bi bi-house"></i> {{ __('Voir le site') }}</a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> {{ __('Se déconnecter') }}</button>
                </form>
            </div>
        </div>
    </div>
</header>
