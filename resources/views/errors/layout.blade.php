{{--
    Error pages (404, 403, 419, 429, 500, 503): standalone on purpose — no database, no session, no site layout —
    so they still display when something is broken. Brand color from config, light / dark like the site.
--}}
@php
    $brand = config('safar.couleur', '#0B4FC4');
    $nom = config('safar.nom', 'BLASTI');
    try {
        $logo = \App\Support\BrandImages::url('blasti-logo.png');
        $logoDark = \App\Support\BrandImages::url('blasti-logo-dark.png');
        $bus = \App\Support\BrandImages::url('blasti-bus.png');
    } catch (\Throwable $e) {
        $logo = $logoDark = asset('assets/img/blasti-logo.png');
        $bus = asset('assets/img/blasti-bus.png');
    }
    try {
        $connecte = auth()->check();
    } catch (\Throwable $e) {
        $connecte = false;
    }
    $rtl = app()->getLocale() === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · @yield('title') | {{ $nom }}</title>
    @include('partials.favicon')
    <script>try { if (localStorage.getItem('darkMode') === 'enabled') document.documentElement.classList.add('dark'); } catch (e) {}</script>
    <style>
        :root { --brand: {{ $brand }}; --bg: #f4f7fb; --card: #fff; --text: #0f172a; --muted: #64748b; --line: #e5eaf2; }
        html.dark { --bg: #0b1220; --card: #111b26; --text: #f1f5f9; --muted: #94a3b8; --line: #26323f; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; font-family: "Segoe UI", Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }
        header { padding: 22px 28px; }
        header img { height: 46px; }
        .logo-dark { display: none; }
        html.dark .logo-light { display: none; }
        html.dark .logo-dark { display: inline; }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px 16px 48px; }
        .box { width: 100%; max-width: 560px; text-align: center; }
        .scene { --bus-w: 86px; position: relative; height: 150px; margin-bottom: 8px; }
        .code { font-size: clamp(88px, 22vw, 140px); font-weight: 800; line-height: 1; letter-spacing: -.04em;
                background: linear-gradient(135deg, var(--brand), color-mix(in srgb, var(--brand) 45%, #fff)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .road { position: absolute; left: 10%; right: 10%; bottom: 10px; height: 4px; border-radius: 4px;
                background: repeating-linear-gradient(90deg, color-mix(in srgb, var(--brand) 35%, transparent) 0 22px, transparent 22px 36px); }
        /* the brand bus drives along the road, turns around at each end, and bounces a little on the way */
        .bus { position: absolute; bottom: 8px; left: 8%; width: var(--bus-w); animation: drive 7s ease-in-out infinite; }
        .bus img { display: block; width: 100%; height: auto; animation: bounce .45s ease-in-out infinite alternate;
                   filter: drop-shadow(0 6px 8px color-mix(in srgb, var(--brand) 30%, transparent)); }
        @keyframes drive { 0% { left: 4%; transform: scaleX(1); } 48% { left: calc(96% - var(--bus-w)); transform: scaleX(1); }
                           50% { left: calc(96% - var(--bus-w)); transform: scaleX(-1); } 98% { left: 4%; transform: scaleX(-1); } 100% { left: 4%; transform: scaleX(1); } }
        @keyframes bounce { from { transform: translateY(0); } to { transform: translateY(-3px); } }
        @media (max-width: 480px) { .scene { --bus-w: 64px; } }
        @media (prefers-reduced-motion: reduce) { .bus, .bus img { animation: none; } .bus { left: calc(50% - var(--bus-w) / 2); } }
        .icon { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; display: grid; place-items: center; font-size: 28px;
                background: color-mix(in srgb, var(--brand) 12%, var(--card)); color: var(--brand); }
        h1 { margin: 0 0 10px; font-size: 1.7rem; }
        p { margin: 0 auto 26px; max-width: 440px; line-height: 1.6; color: var(--muted); font-size: 1.02rem; }
        .detail { display: inline-block; margin: -10px auto 24px; padding: 8px 14px; border-radius: 10px; background: var(--card); border: 1px solid var(--line); color: var(--text); font-size: .92rem; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 999px; font-weight: 600; font-size: .95rem; text-decoration: none; border: 0; cursor: pointer; }
        .btn-primary { background: var(--brand); color: #fff; box-shadow: 0 8px 22px color-mix(in srgb, var(--brand) 35%, transparent); }
        .btn-primary:hover { filter: brightness(1.08); }
        .btn-light { background: var(--card); color: var(--text); border: 1px solid var(--line); }
        .btn-light:hover { border-color: var(--brand); color: var(--brand); }
        footer { padding: 18px; text-align: center; font-size: .85rem; color: var(--muted); }
        footer a { color: var(--brand); text-decoration: none; }
    </style>
</head>
<body>
    <header>
        <a href="{{ url('/') }}"><img src="{{ $logo }}" alt="{{ $nom }}" class="logo-light"><img src="{{ $logoDark }}" alt="{{ $nom }}" class="logo-dark"></a>
    </header>

    <main>
        <div class="box">
            <div class="scene" aria-hidden="true">
                <div class="code">@yield('code')</div>
                <div class="road"></div>
                <div class="bus"><img src="{{ $bus }}" alt=""></div>
            </div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            @hasSection('detail')
                <div class="detail">@yield('detail')</div>
            @endif
            <div class="actions">
                @yield('actions')
            </div>
        </div>
    </main>

    <footer>
        {{ __('Besoin d\'aide ?') }} <a href="{{ url('/aide') }}">{{ __('Centre d\'aide') }}</a> · <a href="mailto:{{ config('safar.contact.email') }}">{{ config('safar.contact.email') }}</a>
    </footer>
</body>
</html>
