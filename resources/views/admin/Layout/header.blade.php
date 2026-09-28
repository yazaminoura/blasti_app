@php
    $me = auth()->user();
    $roleLabel = $me->isSuperAdmin() ? 'Super administrateur' : ($me->roles->first()?->name ?? 'Administrateur');
@endphp
<header class="sa-topbar">
    <button type="button" class="sa-icon-btn d-lg-none" data-sa-sidebar-open aria-label="Ouvrir le menu">
        <i class="bi bi-list"></i>
    </button>

    <nav class="sa-breadcrumb" aria-label="Fil d'Ariane">
        <a href="{{ route('admin') }}">Admin</a>
        <i class="bi bi-chevron-right"></i>
        <span>@yield('title', 'Administration')</span>
    </nav>

    <div class="sa-topbar-actions">
        {{-- Light / dark / system, remembered in this browser --}}
        <div class="sa-theme-switch" role="group" aria-label="Thème">
            <button type="button" data-sa-theme="light" title="Clair"><i class="bi bi-sun"></i></button>
            <button type="button" data-sa-theme="dark" title="Sombre"><i class="bi bi-moon-stars"></i></button>
            <button type="button" data-sa-theme="auto" title="Système"><i class="bi bi-circle-half"></i></button>
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
                <a href="{{ route('profile.edit') }}" class="dropdown-item"><i class="bi bi-person"></i> Mon profil</a>
                @if ($me->isSuperAdmin())
                    <a href="{{ route('admin.apparence.edit') }}" class="dropdown-item"><i class="bi bi-palette"></i> Apparence</a>
                @endif
                <a href="{{ route('home') }}" class="dropdown-item" target="_blank"><i class="bi bi-house"></i> Voir le site</a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Se déconnecter</button>
                </form>
            </div>
        </div>
    </div>
</header>
