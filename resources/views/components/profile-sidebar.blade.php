@props(['name'])
@php
    $user = auth()->user();
    $upcoming = $user->reservations()->whereDate('date_depart', '>=', today())->count();
    $links = [
        __('Principal') => [
            ['client.profile.dashboard.index', 'client.profile.dashboard.*', 'isax-grid-55', __('Tableau de bord'), null],
            ['client.profile.reservations.index', 'client.profile.reservations.*', 'isax-calendar-tick5', __('Mes réservations'), $upcoming ?: null],
            ['wishlist', 'wishlist', 'isax-heart5', __('Mes favoris'), null],
        ],
        __('Compte') => [
            ['client.profile.monprofile.index', 'client.profile.monprofile.*', 'isax-profile-tick5', __('Mon profil'), null],
            ['client.profile.parametres.index', 'client.profile.parametres.*', 'isax-setting-25', __('Paramètres et sécurité'), null],
        ],
    ];
@endphp

<!-- Sidebar -->
<div class="col-xl-3 col-lg-4">
    <div class="card user-sidebar mb-4 mb-lg-0 blasti-sidebar">
        <div class="card-header user-sidebar-header">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $user->profile_image_path }}" alt="" class="avatar avatar-lg rounded-circle flex-shrink-0"
                     style="object-fit: cover;" onerror="this.src='{{ asset('assets/img/users/user-01.jpg') }}'">
                <div class="min-w-0">
                    <h6 class="fs-16 mb-0 text-truncate">{{ $name }}</h6>
                    <span class="fs-13 text-gray-6">{{ __('Membre depuis :date', ['date' => $user->created_at->translatedFormat('F Y')]) }}</span>
                </div>
            </div>
        </div>
        <div class="card-body user-sidebar-body">
            <ul>
                @foreach ($links as $section => $items)
                    <li><span class="fs-13 text-gray-3 fw-medium mb-2 d-block text-uppercase" style="letter-spacing: .04em;">{{ $section }}</span></li>
                    @foreach ($items as [$route, $pattern, $icon, $label, $badge])
                        <li>
                            <a href="{{ route($route) }}" class="d-flex align-items-center @if (request()->routeIs($pattern)) active @endif">
                                <i class="isax {{ $icon }}"></i> <span class="flex-grow-1">{{ $label }}</span>
                                @if ($badge)
                                    <span class="badge bg-primary rounded-pill ms-2">{{ $badge }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                @endforeach
                <li class="mt-2">
                    {{-- Logout only accepts POST --}}
                    <form method="POST" action="{{ route('logout') }}" id="sidebar-logout" class="d-none">@csrf</form>
                    <a href="{{ route('logout') }}" class="d-flex align-items-center pb-0 text-danger"
                       onclick="event.preventDefault(); document.getElementById('sidebar-logout').submit();">
                        <i class="isax isax-logout-15"></i> {{ __('Déconnexion') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->
