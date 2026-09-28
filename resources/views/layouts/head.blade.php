

    <meta name="description" content="{{ __('BLASTI : réservez vos billets de bus entre les villes du Maroc, choisissez votre siège et recevez votre billet en PDF.') }}">
    <meta name="keywords" content="{{ __('billet de bus, autocar, voyage Maroc, réservation bus, Casablanca, Marrakech, Rabat, Tanger, Fès, BLASTI') }}">
    <meta name="author" content="BLASTI">
    <meta property="og:site_name" content="BLASTI">
    <meta property="og:title" content="{{ __('BLASTI : billets de bus au Maroc') }}">
    <meta property="og:description" content="{{ __('Réservez votre place de bus en quelques clics et recevez votre billet en PDF.') }}">
    <meta property="og:image" content="{{ \App\Support\BrandImages::url('blasti-logo.png') }}">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="{{ config('safar.couleur') }}">
    <meta name="robots" content="index, follow">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ \App\Support\BrandImages::url('blasti-icon.png') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ \App\Support\BrandImages::url('blasti-icon.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ \App\Support\BrandImages::url('blasti-icon.png') }}" type="image/png">

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
    <link rel="stylesheet" href="{{asset('assets/plugins/tabler-icons/tabler-icons.css')}}">

    <!-- Fontawesome Icon CSS -->
    <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome/css/fontawesome.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome/css/all.min.css')}}">

    <!-- Fancybox CSS -->
    <link rel="stylesheet" href="{{asset('assets/plugins/fancybox/jquery.fancybox.min.css')}}">

    <!-- Owlcarousel CSS -->
    <link rel="stylesheet" href="{{asset('assets/plugins/owlcarousel/owl.carousel.min.css')}}">

    <!-- Iconsax CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/iconsax.css')}}">

    <!-- Datepicker CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/bootstrap-datetimepicker.min.css')}}">

    <!-- Style CSS -->
    <link rel="stylesheet" href="{{asset('assets/css/style.css') . '?v=' . @filemtime(public_path('assets/css/style.css'))}}">
    <link rel="stylesheet" href="{{ asset('assets/css/blasti.css') . '?v=' . @filemtime(public_path('assets/css/blasti.css')) }}">
    {{-- Main color chosen in Admin > Paramètres > Apparence (overrides the defaults of style.css) --}}
    <style>:root { --brand: {{ config('safar.couleur') }}; --brand-rgb: {{ \App\Models\Parametre::rgb(config('safar.couleur')) }}; }</style>
