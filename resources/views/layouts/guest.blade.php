<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Mon compte') }} | BLASTI</title>

        {{-- same light / dark choice as the rest of the site (applied before the page is drawn: no white flash) --}}
        <script src="{{ asset('assets/js/theme-script.js') }}"></script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])





    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets\img\admin\logo.png') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('assets\img\admin\logo.png') }}" type="image/x-icon">

    @if(app()->getLocale() == 'ar')
        <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    @endif

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">

    <!-- Iconsax CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/iconsax.css') }}">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') . '?v=' . @filemtime(public_path('assets/css/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/blasti.css') . '?v=' . @filemtime(public_path('assets/css/blasti.css')) }}">
    {{-- Main color chosen in Admin > Paramètres > Apparence (overrides the defaults of style.css) --}}
    <style>:root { --brand: {{ config('safar.couleur') }}; --brand-rgb: {{ \App\Models\Parametre::rgb(config('safar.couleur')) }}; }</style>
    </head>
    <body class="font-sans text-gray-900 antialiased">


            {{-- Form in the middle on the brand background (gradient + zellige pattern, see blasti.css) --}}
            <div class="bl-auth-bg">
                <a href="{{ route('home') }}" class="bl-auth-home"><i class="isax isax-arrow-left-2"></i><span>{{ __('Retour à l\'accueil') }}</span></a>
                {{ $slot }}
            </div>

            <div class="coprright-footer">
                <p class="fs-14">&copy; {{ date('Y') }} · {{ __('Tous droits réservés') }}, <a href="{{ route('home') }}" class="text-primary fw-medium">BLASTI</a>
                </p>        </div>
    <!-- Scripts -->
    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

    
       <!-- Toggle Password Visibility -->
       {{-- <script>
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.querySelector('.pass-input');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('isax-eye');
            this.querySelector('i').classList.toggle('isax-eye-slash');
        });
    </script> --}}


    <script>
        document.querySelectorAll('.toggle-password').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                // Find the closest input field relative to the clicked toggle button
                const passwordInput = this.closest('.input-icon').querySelector('.pass-input');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the eye icon
                this.querySelector('i').classList.toggle('isax-eye');
                this.querySelector('i').classList.toggle('isax-eye-slash');
            });
        });
    </script>
    </body>
</html>
