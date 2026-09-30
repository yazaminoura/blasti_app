

    <meta name="description" content="{{ __(':brand : réservez vos billets de bus entre les villes du Maroc, choisissez votre siège et recevez votre billet en PDF.', ['brand' => config('safar.nom')]) }}">
    <meta name="keywords" content="{{ __('billet de bus, autocar, voyage Maroc, réservation bus, Casablanca, Marrakech, Rabat, Tanger, Fès, :brand', ['brand' => config('safar.nom')]) }}">
    <meta name="author" content="{{ config('safar.nom') }}">
    <meta property="og:site_name" content="{{ config('safar.nom') }}">
    <meta property="og:title" content="{{ __(':brand : billets de bus au Maroc', ['brand' => config('safar.nom')]) }}">
    <meta property="og:description" content="{{ __('Réservez votre place de bus en quelques clics et recevez votre billet en PDF.') }}">
    <meta property="og:image" content="{{ \App\Support\BrandImages::url('blasti-logo.png') }}">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="{{ config('safar.couleur') }}">
    <meta name="robots" content="index, follow">

    <!-- Apple Touch Icon -->


    <!-- Favicon -->
    @include('partials.favicon')

    <!-- Theme Settings Js -->
	<script src="{{asset('assets/js/theme-script.js')}}"></script>

    <link rel="stylesheet" href="{{asset('assets/css/animate.css')}}">
    @if(app()->getLocale() == 'ar')
        <link rel="stylesheet" href="{{asset('assets/css/bootstrap.rtl.min.css')}}">
    @else
        <link rel="stylesheet" href="{{asset('assets/css/bootstrap.min.css')}}">
    @endif

    <!-- Main.css -->
    <link rel="stylesheet" href="{{asset('assets/css/meanmenu.css')}}">

    <!-- Tabler Icon CSS -->

    <!-- Fontawesome Icon CSS -->
    <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome/css/fontawesome.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome/css/all.min.css')}}">

    <!-- Fancybox CSS -->

    <!-- Owlcarousel CSS -->
    <link rel="stylesheet" href="{{asset('assets/plugins/owlcarousel/owl.carousel.min.css')}}">

    <!-- Iconsax CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/iconsax.css')}}">

    <!-- Datepicker CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/bootstrap-datetimepicker.min.css')}}">

    <!-- Style CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/style.css') . '?v=' . @filemtime(public_path('assets/css/style.css'))}}">
    <link rel="stylesheet" href="{{ asset('assets/css/blasti.css') . '?v=' . @filemtime(public_path('assets/css/blasti.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/blasti-popups.css') . '?v=' . @filemtime(public_path('assets/css/blasti-popups.css')) }}">
    {{-- Main color chosen in Admin > Paramètres > Apparence (overrides the defaults of style.css) --}}
    <style>:root { --brand: {{ config('safar.couleur') }}; --brand-rgb: {{ \App\Models\Parametre::rgb(config('safar.couleur')) }}; }</style>
