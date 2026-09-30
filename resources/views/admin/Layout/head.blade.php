@php
    $brand = config('safar.couleur');
@endphp
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Administration') | {{ config('safar.nom') }} Admin</title>
    @include('partials.favicon')

    {{-- Theme (light / dark / system) and collapsed sidebar, applied before the first paint to avoid a flash --}}
    <script>
        (function () {
            var root = document.documentElement, choice = 'auto', collapsed = false;
            try {
                choice = localStorage.getItem('safar-admin-theme') || 'auto';
                var stored = localStorage.getItem('safar-admin-sidebar');
                // no choice yet: icons-only menu on a tablet held sideways (992-1279 px), full menu on a computer
                collapsed = stored ? stored === 'collapsed' : (window.innerWidth >= 992 && window.innerWidth < 1280);
            } catch (e) {}
            var dark = choice === 'dark' || (choice === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
            root.setAttribute('data-theme-choice', choice);
            if (collapsed) root.classList.add('sa-collapsed');
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    @if (app()->getLocale() == 'ar')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" crossorigin="anonymous">
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.17.2/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/blasti-popups.css') }}?v={{ @filemtime(public_path('assets/css/blasti-popups.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/safar-admin.css') }}?v={{ @filemtime(public_path('assets/admin/safar-admin.css')) }}">

    {{-- Brand color chosen in Paramètres > Apparence; every accent of the admin derives from it --}}
    <style>
        :root {
            --brand: {{ $brand }};
            --brand-rgb: {{ \App\Models\Parametre::rgb($brand) }};
            --brand-rgb-light: {{ \App\Models\Parametre::rgb($brand, 0.45) }};
        }
    </style>
    @stack('styles')
</head>
