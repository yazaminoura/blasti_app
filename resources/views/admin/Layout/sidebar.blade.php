@php
    // [section title => [ [permission, route, active pattern(s), icon, label], ... ]]
    $menu = [
        '' => [
            ['dashboard.read', 'admin', 'admin', 'bi-grid-1x2', 'Tableau de bord'],
            ['statistiques.read', 'admin.statistiques', 'admin.statistiques', 'bi-bar-chart-line', 'Statistiques'],
            ['finance.read', 'admin.alertes', 'admin.alertes', 'bi-exclamation-triangle', 'Alertes contrôle'],
        ],
        'Activité' => [
            ['reservations.create', 'reservation.admin.guichet', 'reservation.admin.guichet*', 'bi-shop-window', 'Guichet (vente)'],
            ['reservations.read', 'reservation.admin.index', ['reservation.admin.index', 'reservation.admin.show'], 'bi-ticket-perforated', 'Réservations'],
            ['scanner.use', 'reservation.admin.scanner', 'reservation.admin.scanner', 'bi-qr-code-scan', 'Scanner les billets'],
            ['chauffeur.view', 'chauffeur.index', 'chauffeur.*', 'bi-compass', 'Espace Chauffeur'],
            ['voyages.read', 'voyages.index', 'voyages.*', 'bi-signpost-split', 'Voyages'],
            ['clients.read', 'admin.clients.index', 'admin.clients.*', 'bi-person-lines-fill', 'Clients'],
            ['promotions.read', 'promotions.index', 'promotions.*', 'bi-percent', 'Promotions'],
            ['avis.read', 'avis.index', 'avis.*', 'bi-star', 'Avis'],
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
            [null, 'admin.coordonnees.edit', 'admin.coordonnees.*', 'bi-telephone', 'Coordonnées'],
            [null, 'admin.tarifs.edit', 'admin.tarifs.*', 'bi-graph-up-arrow', 'Tarifs par défaut'],
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
        <span class="sa-brand-tag">{{ __('Admin') }}</span>
    </div>

    <nav class="sa-nav">
        @foreach ($menu as $section => $items)
            @php $visible = array_filter($items, fn ($item) => $allowed($item[0])); @endphp
            @continue(empty($visible))

            @if ($section !== '')
                <div class="sa-nav-label">{{ __($section) }}</div>
            @endif

            @foreach ($visible as [$permission, $route, $pattern, $icon, $label])
                <a href="{{ route($route) }}" class="sa-nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" title="{{ __($label) }}">
                    <i class="bi {{ $icon }}"></i>
                    <span>{{ __($label) }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sa-sidebar-footer">
        <a href="{{ route('home') }}" class="sa-nav-link" target="_blank" title="{{ __('Voir le site') }}">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>{{ __('Voir le site') }}</span>
        </a>
        <button type="button" class="sa-nav-link sa-collapse-btn d-none d-lg-flex" data-sa-sidebar-collapse title="{{ __('Réduire le menu') }}">
            <i class="bi bi-layout-sidebar-inset"></i>
            <span>{{ __('Réduire le menu') }}</span>
        </button>
    </div>
</aside>
