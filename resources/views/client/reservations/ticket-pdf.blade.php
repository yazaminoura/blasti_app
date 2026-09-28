<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Billet de Voyage') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background: #f5f5f5;
        }
        .ticket-container {
            width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid {{ config('safar.couleur') }};
        }
        .header h2 {
            color: {{ config('safar.couleur') }};
            margin: 0;
        }
        .ticket-info {
            margin-top: 20px;
        }
        .info-item {
            margin: 8px 0;
            font-size: 16px;
        }
        .info-item strong {
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="ticket-container">
        <div class="logo-container">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(\App\Support\BrandImages::path('blasti-logo.png'))) }}" style="height: 56px;">
        </div>
        <div class="header">
            <h2>{{ __('Billet de Voyage') }}</h2>
        </div>

        @php
            $d = fn ($date, $time) => __(':date à :heure', ['date' => \Carbon\Carbon::parse($date)->format('d/m/Y'), 'heure' => \Carbon\Carbon::parse($time)->format('H:i')]);
        @endphp
        <div class="ticket-info">
            <p class="info-item"><strong>{{ __('Passager :') }}</strong> {{ $reservation->user?->name }}</p>
            <p class="info-item"><strong>{{ __('N° du billet :') }}</strong> {{ $reservation->id }}</p>
            <p class="info-item"><strong>{{ __('Trajet :') }}</strong> {{ __($reservation->villeDepart?->ville) }} → {{ __($reservation->villeArrivee?->ville) }}</p>
            <p class="info-item"><strong>{{ __('Départ :') }}</strong> {{ $d($reservation->date_depart, $reservation->heure_depart) }}</p>
            <p class="info-item"><strong>{{ __('Arrivée prévue :') }}</strong> {{ $d($reservation->date_arrivee, $reservation->heure_arrivee) }}</p>
            <p class="info-item"><strong>{{ __('Société / autocar :') }}</strong> {{ $reservation->autocar?->societe?->raison_social ?? '—' }} · {{ $reservation->autocar?->matricule ?? '—' }}</p>
            <p class="info-item"><strong>{{ __('Siège :') }}</strong> {{ __('N° :num', ['num' => $reservation->num_siege]) }}</p>
            <p class="info-item"><strong>{{ __('Total payé :') }}</strong> {{ number_format($reservation->prix + $reservation->frais, 2, ',', ' ') }} DH</p>
            <p class="info-item"><strong>{{ __('Mode de paiement :') }}</strong> {{ __($reservation->modeReglement?->mode_reglement ?? '—') }} ({{ $reservation->isPaid() ? __('payé') : __('à régler à l\'embarquement') }})</p>
        </div>

        <div class="footer">
            {{ __('Merci d\'avoir voyagé avec BLASTI. Présentez ce billet à l\'embarquement, 15 minutes avant le départ. Bon voyage !') }}
        </div>
    </div>

</body>
</html>
