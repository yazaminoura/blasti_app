{{-- Small ticket for a receipt printer (80 or 58 mm roll), one per seat, black on white. Also prints on any printer. --}}
@php
    $mm = $largeur;
    $qr = $mm === 58 ? 150 : 200;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billets {{ $billets->first()?->commande }}</title>
    <style>
        @page { size: {{ $mm }}mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; background: #fff; color: #000; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: {{ $mm === 58 ? 11 : 12 }}px; }
        .ticket { width: {{ $mm }}mm; padding: 4mm 3mm 6mm; margin: 0 auto; text-align: center; page-break-after: always; break-after: page; }
        .ticket:last-child { page-break-after: auto; break-after: auto; }
        .brand { font-size: 1.6em; font-weight: 900; letter-spacing: .08em; }
        .sep { border-top: 1px dashed #000; margin: 3mm 0; }
        .route { font-size: 1.35em; font-weight: 700; line-height: 1.25; }
        .big { font-size: 1.25em; font-weight: 700; }
        .seat { display: inline-block; border: 2px solid #000; border-radius: 3mm; padding: 1mm 4mm; font-size: 1.6em; font-weight: 900; margin: 2mm 0; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        td { padding: .6mm 0; vertical-align: top; }
        td + td { text-align: right; font-weight: 700; }
        .qr img { width: {{ $qr }}px; height: {{ $qr }}px; }
        .small { font-size: .85em; }
        .screen-bar { font-family: system-ui, sans-serif; display: flex; gap: 8px; justify-content: center; padding: 10px; background: #f1f5f9; }
        .screen-bar a, .screen-bar button { font: inherit; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #0f172a; text-decoration: none; cursor: pointer; }
        @media print { .screen-bar { display: none; } }
    </style>
</head>
<body>
    <div class="screen-bar">
        <button type="button" onclick="window.print()">Imprimer</button>
        <a href="?largeur={{ $mm === 80 ? 58 : 80 }}">Rouleau {{ $mm === 80 ? 58 : 80 }} mm</a>
        <a href="javascript:window.close()">Fermer</a>
    </div>

    @foreach ($billets as $r)
        @php $d = $r->departAt(); $a = \Carbon\Carbon::parse(\Carbon\Carbon::parse($r->date_arrivee)->toDateString() . ' ' . $r->heure_arrivee); @endphp
        <div class="ticket">
            <div class="brand">{{ mb_strtoupper(config('safar.nom')) }}</div>
            <div class="small">Billet de bus · N° {{ $r->id }}</div>
            <div class="sep"></div>

            <div class="route">{{ $r->villeDepart?->ville }}<br>→ {{ $r->villeArrivee?->ville }}</div>
            <div class="big" style="margin-top: 2mm;">{{ ucfirst($d->translatedFormat('D d M Y')) }} · {{ $d->format('H:i') }}</div>
            <div class="small">Arrivée prévue {{ $a->format('H:i') }}</div>
            <div class="seat">SIÈGE {{ $r->num_siege }}</div>

            <table>
                <tr><td>Passager</td><td>{{ $r->passager() }}</td></tr>
                <tr><td>Compagnie</td><td>{{ $r->autocar?->societe?->raison_social ?? '—' }}</td></tr>
                <tr><td>Autocar</td><td>{{ $r->autocar?->matricule ?? '—' }}</td></tr>
                <tr><td>Prix</td><td>{{ number_format($r->total(), 2, ',', ' ') }} DH</td></tr>
                <tr><td>Commande</td><td>{{ $r->commande }}</td></tr>
            </table>

            <div class="sep"></div>
            <div class="qr"><img src="{{ $r->qrCodeDataUri($qr) }}" alt="QR"></div>
            <div class="small">Présentez ce QR code au contrôleur.<br>Vendu le {{ $r->created_at?->format('d/m/Y H:i') }}</div>
        </div>
    @endforeach

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>
