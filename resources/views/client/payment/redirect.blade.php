<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.favicon')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Paiement sécurisé') }} | {{ config('safar.nom') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, Helvetica, sans-serif; background: #f3f5f9; color: #1f2937; }
        .box { background: #fff; border-radius: 14px; padding: 32px 28px; max-width: 420px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .spinner { width: 42px; height: 42px; margin: 0 auto 16px; border: 4px solid #e5e7eb; border-top-color: {{ config('safar.couleur') }}; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        button { margin-top: 16px; background: {{ config('safar.couleur') }}; color: #fff; border: 0; padding: 12px 22px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        small { color: #6b7280; }
    </style>
</head>
<body>
    <form class="box" method="POST" action="{{ $gateway }}" id="cmi-form">
        <div class="spinner"></div>
        <h2 style="margin: 0 0 8px;">{{ __('Paiement sécurisé') }}</h2>
        <p style="margin: 0;">{!! __('Vous allez être redirigé vers la page de paiement du CMI pour régler <strong>:montant DH</strong>.', ['montant' => e(number_format((float) \App\Support\Cmi::amount($reservation), 2, ',', ' '))]) !!}</p>
        <small>{{ __('Votre siège est réservé pendant :minutes minutes.', ['minutes' => \App\Models\Reservation::DELAI_PAIEMENT_MINUTES]) }}</small>
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <noscript><br></noscript>
        <button type="submit">{{ __('Continuer vers le paiement') }}</button>
    </form>
    <script>document.getElementById('cmi-form').submit();</script>
</body>
</html>
