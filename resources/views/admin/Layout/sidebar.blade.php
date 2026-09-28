@php
    // [section title => [ [permission, route, active pattern(s), icon, label], ... ]]
    $menu = [
        '' => [
            ['dashboard.read', 'admin', 'admin', 'bi-grid-1x2', 'Tableau de bord'],
        ],
        'Activité' => [
            ['reservations.read', 'reservation.admin.index', 'reservation.admin.*', 'bi-ticket-perforated', 'Réservations'],
            ['voyages.read', 'voyages.index', 'voyages.*', 'bi-signpost-split', 'Voyages'],
            ['utilisateurs.read', 'admin.clients.index', 'admin.clients.*', 'bi-person-lines-fill', 'Clients'],
        ],
        'Flotte' => [
            ['societes.read', 'societes.index', 'societes.*', 'bi-buildings', 'Sociétés'],
            ['autocars.read', 'autocars.index', 'autocars.*', 'bi-bus-front', 'Autocars'],
            ['equipements.read', 'equipements.index', 'equipements.*', 'bi-tools', 'Équipements'],
            ['equipements.read', 'autocarequipements.index', 'autocarequipements.*', 'bi-link-45deg', 'Équipements par autocar'],
            ['options.read', 'options.index', 'options.*', 'bi-stars', 'Options'],
            ['options.read', 'autocaroptions.index', 'autocaroptions.*', 'bi-link-45deg', 'Options par autocar'],
        ],
        'Paramètres' => [
            ['villes.read', 'villes.index', 'villes.*', 'bi-geo-alt', 'Villes'],
            ['type voyages.read', 'type_voyages.index', 'type_voyages.*', 'bi-tags', 'Types de voyage'],
            ['mode reglements.read', 'modeReglements.index', 'modeReglements.*', 'bi-credit-card', 'Modes de règlement'],
            ['utilisateurs.read', 'admin.users.index', ['admin.users.*', 'admin.roles.*'], 'bi-shield-lock', 'Utilisateurs & rôles'],
            // null = super admin only
            [null, 'admin.apparence.edit', 'admin.apparence.*', 'bi-palette', 'Apparence'],
        ],
    ];
    $user = auth()->user();
    $allowed = fn ($permission) => $permission === null ? $user->isSuperAdmin() : $user->hasPermission($permission);
@endphp

<aside class="sa-sidebar" id="sa-sidebar" aria-label="Menu principal">
    <div class="sa-sidebar-brand">
        <a href="{{ route('admin') }}" class="text-decoration-none">
            <x-admin.logo :size="46" />
        </a>
        <span class="sa-brand-tag">Admin</span>
    </div>

    <nav class="sa-nav">
        @foreach ($menu as $section => $items)
            @php $visible = array_filter($items, fn ($item) => $allowed($item[0])); @endphp
            @continue(empty($visible))

            @if ($section !== '')
                <div class="sa-nav-label">{{ $section }}</div>
            @endif

            @foreach ($visible as [$permission, $route, $pattern, $icon, $label])
                <a href="{{ route($route) }}" class="sa-nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" title="{{ $label }}">
                    <i class="bi {{ $icon }}"></i>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sa-sidebar-footer">
        <a href="{{ route('home') }}" class="sa-nav-link" target="_blank" title="Voir le site">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>Voir le site</span>
        </a>
        <button type="button" class="sa-nav-link sa-collapse-btn d-none d-lg-flex" data-sa-sidebar-collapse title="Réduire le menu">
            <i class="bi bi-layout-sidebar-inset"></i>
            <span>Réduire le menu</span>
        </button>
    </div>
</aside>
