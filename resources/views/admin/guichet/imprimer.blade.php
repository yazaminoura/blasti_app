{{--
    Same boarding pass as the PDF (client.reservations.ticket-pdf): landscape, QR code on the right stub.
    On a receipt printer roll (80 or 58 mm wide) the pass is printed turned a quarter along the roll,
    like airline boarding passes; on screen it is shown the right way up. One pass per seat.
    A thermal printer prints the colours as black / grey.
--}}
@php
    $mm = $largeur;                          // roll width
    $h = $mm === 58 ? 50 : 72;               // pass height = printable width of the roll (mm)
    $w = round($h * 2.35);                   // pass width (mm), about the PDF proportions
    $k = $h / 72;                            // scale (80 mm roll = 1)
    $img = fn (string $file) => 'data:image/png;base64,' . base64_encode(file_get_contents(\App\Support\BrandImages::path($file)));
    $logo = $img('blasti-logo-dark.png');
    $bus = $img('blasti-bus.png');
    $brand = config('safar.couleur');
    $dh = fn ($n) => number_format($n, 2, ',', ' ') . ' DH';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Billet de Voyage') }} {{ $billets->first()?->commande }}</title>
    @include('partials.favicon')
    <style>
        @page { size: {{ $mm }}mm {{ $w + 6 }}mm; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { margin: 0; background: #fff; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; font-size: {{ round(9.5 * $k, 2) }}pt; }

        .pass { width: {{ $w }}mm; height: {{ $h }}mm; background: #fff; display: flex; flex-direction: column; overflow: hidden; }
        .band { background: {{ $brand }}; color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 5mm; height: {{ round(13 * $k, 1) }}mm; flex: none; }
        .band img { height: {{ round(9 * $k, 1) }}mm; }
        .band .title { font-size: .8em; letter-spacing: 2px; text-transform: uppercase; opacity: .85; text-align: right; }
        .band .num { font-size: 1.45em; font-weight: bold; text-align: right; }
        .band .num small { font-weight: normal; font-size: .55em; opacity: .85; }

        .body { flex: 1; display: flex; min-height: 0; }
        .main { flex: 1; padding: {{ round(3.5 * $k, 1) }}mm 4mm 0 5mm; min-width: 0; }
        .stub { width: {{ round(44 * $k, 1) }}mm; flex: none; border-left: 2.5px dashed #cbd5e1; background: #f8fafc; text-align: center; padding: {{ round(2.5 * $k, 1) }}mm 2mm 0; }

        .label { font-size: .72em; letter-spacing: 1px; text-transform: uppercase; color: #64748b; }
        .city { font-size: 2em; font-weight: bold; color: #0f172a; line-height: 1.05; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .time { font-size: 1.55em; font-weight: bold; color: {{ $brand }}; }
        .date { font-size: .88em; color: #475569; }
        .route { display: flex; align-items: flex-start; gap: 2mm; }
        .route > div:first-child, .route > div:last-child { flex: 1; min-width: 0; }
        .route > div:last-child { text-align: right; }
        .trip { width: {{ round(26 * $k, 1) }}mm; flex: none; text-align: center; padding-top: {{ round(4 * $k, 1) }}mm; }
        .trip img { height: {{ round(6 * $k, 1) }}mm; }
        .line { border-top: 2px dashed #cbd5e1; margin: .6mm 0 .4mm; }

        .sep { border-top: 1px solid #e2e8f0; margin: {{ round(2.5 * $k, 1) }}mm 0 {{ round(1.2 * $k, 1) }}mm; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: {{ round(1.4 * $k, 1) }}mm 4mm; }
        .val { font-size: 1.15em; font-weight: bold; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .note { font-size: .74em; color: #475569; line-height: 1.3; }

        .seat-big { font-size: 2.6em; font-weight: bold; color: {{ $brand }}; line-height: 1; }
        .stub img { width: {{ round(24 * $k, 1) }}mm; height: {{ round(24 * $k, 1) }}mm; margin: {{ round(1 * $k, 1) }}mm 0 .5mm; }

        .foot { flex: none; background: #f1f5f9; color: #64748b; font-size: .74em; display: flex; justify-content: space-between; gap: 3mm; align-items: center; padding: 0 5mm; height: {{ round(6 * $k, 1) }}mm; }
        .foot span:last-child { white-space: nowrap; }

        .screen-bar { font-family: system-ui, sans-serif; display: flex; gap: 8px; justify-content: center; padding: 10px; margin: 0 -16px 16px; background: #e2e8f0; }
        .screen-bar a, .screen-bar button { font: 14px system-ui, sans-serif; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #0f172a; text-decoration: none; cursor: pointer; }

        /* screen: the pass the right way up, one under the other */
        @media screen {
            body { background: #f1f5f9; padding: 0 16px 24px; }
            .sheet { width: max-content; margin: 0 auto 16px; box-shadow: 0 2px 12px rgba(15, 23, 42, .15); }
        }
        /* roll: each pass turned a quarter to run along the paper */
        @media print {
            .screen-bar { display: none; }
            body { padding: 0; }
            .sheet { position: relative; width: {{ $mm }}mm; height: {{ $w + 5 }}mm; overflow: hidden; page-break-after: always; break-after: page; }
            .sheet:last-child { page-break-after: auto; break-after: auto; }
            .pass { position: absolute; top: 3mm; left: {{ $h + ($mm - $h) / 2 }}mm; transform-origin: top left; transform: rotate(90deg); }
        }
    </style>
</head>
<body>
    <div class="screen-bar">
        <button type="button" onclick="window.print()">Imprimer</button>
        <a href="?largeur={{ $mm === 58 ? 80 : 58 }}">Rouleau {{ $mm === 58 ? 80 : 58 }} mm</a>
        <a href="javascript:window.close()">Fermer</a>
    </div>

    @foreach ($billets as $r)
        @php
            $d = $r->departAt();
            $a = \Carbon\Carbon::parse(\Carbon\Carbon::parse($r->date_arrivee)->toDateString() . ' ' . $r->heure_arrivee);
            $minutes = $d->diffInMinutes($a);
        @endphp
        <div class="sheet">
            <div class="pass">
                {{-- header band --}}
                <div class="band">
                    <img src="{{ $logo }}" alt="{{ config('safar.nom') }}">
                    <div>
                        <div class="title">{{ __('Billet de Voyage') }}@if ($billets->count() > 1) · {{ $loop->iteration }}/{{ $billets->count() }}@endif</div>
                        <div class="num">N° {{ $r->id }}@if ($r->commande) <small>· {{ $r->commande }}</small>@endif</div>
                    </div>
                </div>

                <div class="body">
                    <div class="main">
                        {{-- route --}}
                        <div class="route">
                            <div>
                                <div class="label">{{ __('Départ') }}</div>
                                <div class="city">{{ __($r->villeDepart?->ville) }}</div>
                                <div class="time">{{ $d->format('H:i') }}</div>
                                <div class="date">{{ ucfirst($d->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY')) }}</div>
                            </div>
                            <div class="trip">
                                <img src="{{ $bus }}" alt="">
                                <div class="line"></div>
                                <div class="date">{{ intdiv($minutes, 60) }} h {{ str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) }}</div>
                            </div>
                            <div>
                                <div class="label">{{ __('Arrivée prévue') }}</div>
                                <div class="city">{{ __($r->villeArrivee?->ville) }}</div>
                                <div class="time">{{ $a->format('H:i') }}</div>
                                <div class="date">{{ ucfirst($a->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY')) }}</div>
                            </div>
                        </div>

                        <div class="sep"></div>

                        {{-- details --}}
                        <div class="grid">
                            <div><div class="label">{{ __('Passager') }}</div><div class="val">{{ $r->passager() }}</div></div>
                            <div><div class="label">{{ __('Compagnie · autocar') }}</div><div class="val">{{ $r->autocar?->societe?->raison_social ?? '—' }}</div><div class="date">{{ __('Autocar') }} {{ $r->autocar?->matricule ?? '—' }}</div></div>
                            <div><div class="label">{{ __('Prix') }}</div><div class="val">{{ $dh($r->total()) }}</div></div>
                            {{-- same rule as the PDF: no paid stamp, the controller scans the QR code --}}
                            <div class="note">{{ __('Vérifié en scannant le QR code. Vous payez au guichet ou à bord ? Vous recevez un reçu par e-mail : pas de reçu = signalez-le au :telephone.', ['telephone' => config('safar.contact.telephone')]) }}</div>
                        </div>
                    </div>

                    {{-- tear-off stub --}}
                    <div class="stub">
                        <div class="label">{{ __('Siège') }}</div>
                        <div class="seat-big">{{ $r->num_siege }}</div>
                        <img src="{{ $r->qrCodeDataUri(220) }}" alt="QR">
                        <div class="label" style="letter-spacing: .4px;">{{ __('À scanner à l\'embarquement') }}</div>
                        <div class="date">{{ $d->format('d/m/Y') }} · {{ $d->format('H:i') }}</div>
                    </div>
                </div>

                <div class="foot">
                    <span>{{ __('Présentez ce billet (imprimé ou sur votre téléphone) 15 minutes avant le départ, avec une pièce d\'identité.') }}</span>
                    <span>{{ config('safar.contact.telephone') }}</span>
                </div>
            </div>
        </div>
    @endforeach

    <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
