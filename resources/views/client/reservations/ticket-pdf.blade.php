{{--
    Boarding pass, one A5 landscape page per ticket: $reservations (every seat of an order) or a single $reservation.
    dompdf: tables only (no flexbox), built-in Arial (no → glyph, hence the drawn route line).
--}}
@php
    $billets = isset($reservations) ? collect($reservations) : collect([$reservation]);
    $img = fn (string $file) => 'data:image/png;base64,' . base64_encode(file_get_contents(\App\Support\BrandImages::path($file)));
    $logo = $img('blasti-logo-dark.png');
    $bus = $img('blasti-bus.png');
    $brand = config('safar.couleur');
    $dh = fn ($n) => number_format($n, 2, ',', ' ') . ' DH';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Billet de Voyage') }}</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: Arial, sans-serif; color: #1e293b; font-size: 14px; }
        /* A5 landscape = 595 x 420 pt: sized in pt so the pass fills the sheet */
        .page { width: 100%; height: 419pt; position: relative; }
        .page-break { page-break-after: always; }
        table { border-collapse: collapse; }

        .band { background: {{ $brand }}; color: #fff; height: 92px; }
        .band td { padding: 0 32px; vertical-align: middle; }
        .band .title { font-size: 13px; letter-spacing: 2px; text-transform: uppercase; opacity: .85; }
        .band .num { font-size: 22px; font-weight: bold; }

        .body { height: 385px; }
        .main { padding: 26px 30px 0 32px; vertical-align: top; }
        .stub { width: 250px; border-left: 3px dashed #cbd5e1; padding: 24px 18px 0; text-align: center; vertical-align: top; background: #f8fafc; }

        .label { font-size: 11.5px; letter-spacing: 1px; text-transform: uppercase; color: #64748b; }
        .city { font-size: 36px; font-weight: bold; color: #0f172a; line-height: 1.1; }
        .time { font-size: 26px; font-weight: bold; color: {{ $brand }}; }
        .date { font-size: 14px; color: #475569; }
        .line { border-top: 2px dashed #cbd5e1; }

        .grid td { padding: 11px 12px 11px 0; vertical-align: top; }
        .val { font-size: 18px; font-weight: bold; color: #0f172a; margin-top: 2px; }

        .stamp { display: inline-block; padding: 8px 14px; border-radius: 8px; font-size: 15px; font-weight: bold; letter-spacing: .5px; }
        .stamp.paid { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .stamp.due { background: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }

        .seat-big { font-size: 56px; font-weight: bold; color: {{ $brand }}; line-height: 1; }
        .foot { position: absolute; left: 0; right: 0; bottom: 0; height: 46px; background: #f1f5f9; color: #64748b; font-size: 12px; }
        .foot td { padding: 0 32px; vertical-align: middle; }
    </style>
</head>
<body>
@foreach ($billets as $reservation)
    @php
        $r = $reservation;
        $d = \Carbon\Carbon::parse(\Carbon\Carbon::parse($r->date_depart)->toDateString() . ' ' . $r->heure_depart);
        $a = \Carbon\Carbon::parse(\Carbon\Carbon::parse($r->date_arrivee)->toDateString() . ' ' . $r->heure_arrivee);
        $minutes = $d->diffInMinutes($a);
        $reste = $r->resteAPayer();
    @endphp
    <div class="page {{ $loop->last ? '' : 'page-break' }}">
        {{-- header band --}}
        <table class="band" width="100%">
            <tr>
                <td><img src="{{ $logo }}" style="height: 58px;"></td>
                <td align="right">
                    <div class="title">{{ __('Billet de Voyage') }}@if ($billets->count() > 1) · {{ $loop->iteration }}/{{ $billets->count() }}@endif</div>
                    <div class="num">N° {{ $r->id }}@if ($r->commande) <span style="font-weight: normal; font-size: 11px; opacity: .85;">· {{ $r->commande }}</span>@endif</div>
                </td>
            </tr>
        </table>

        <table width="100%" class="body">
            <tr>
                <td class="main">
                    {{-- route --}}
                    <table width="100%">
                        <tr>
                            <td width="36%" style="vertical-align: top;">
                                <div class="label">{{ __('Départ') }}</div>
                                <div class="city">{{ __($r->villeDepart?->ville) }}</div>
                                <div class="time">{{ $d->format('H:i') }}</div>
                                <div class="date">{{ ucfirst($d->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY')) }}</div>
                            </td>
                            <td width="28%" style="vertical-align: middle; text-align: center; padding: 0 6px;">
                                <img src="{{ $bus }}" style="height: 34px;">
                                <div class="line" style="margin-top: 4px;"></div>
                                <div class="date" style="margin-top: 3px;">{{ intdiv($minutes, 60) }} h {{ str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) }}</div>
                            </td>
                            <td width="36%" style="vertical-align: top; text-align: right;">
                                <div class="label">{{ __('Arrivée prévue') }}</div>
                                <div class="city">{{ __($r->villeArrivee?->ville) }}</div>
                                <div class="time">{{ $a->format('H:i') }}</div>
                                <div class="date">{{ ucfirst($a->locale(app()->getLocale())->isoFormat('ddd D MMM YYYY')) }}</div>
                            </td>
                        </tr>
                    </table>

                    <div style="border-top: 1px solid #e2e8f0; margin: 22px 0 10px;"></div>

                    {{-- details --}}
                    <table class="grid" width="100%">
                        <tr>
                            <td width="50%"><div class="label">{{ __('Passager') }}</div><div class="val">{{ $r->user?->name }}</div></td>
                            <td width="50%"><div class="label">{{ __('Compagnie · autocar') }}</div><div class="val">{{ $r->autocar?->societe?->raison_social ?? '—' }}</div><div class="date">{{ __('Autocar') }} {{ $r->autocar?->matricule ?? '—' }}</div></td>
                        </tr>
                        <tr>
                            <td><div class="label">{{ __('Prix') }}</div><div class="val">{{ $dh($r->total()) }} <span style="font-weight: normal; color: #64748b; font-size: 10px;">· {{ __($r->modeReglement?->mode_reglement ?? '—') }}</span></div></td>
                            <td>
                                <div class="label" style="margin-bottom: 3px;">{{ __('Paiement') }}</div>
                                @if ($reste > 0)
                                    <span class="stamp due">{{ $r->isPaid() ? __('SUPPLÉMENT : :montant', ['montant' => $dh($reste)]) : __('À PAYER : :montant', ['montant' => $dh($reste)]) }}</span>
                                    <div class="date" style="margin-top: 4px;">{{ __('à régler à l\'embarquement') }}</div>
                                @else
                                    <span class="stamp paid">{{ __('PAYÉ') }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>

                {{-- tear-off stub --}}
                <td class="stub">
                    <div class="label">{{ __('Siège') }}</div>
                    <div class="seat-big">{{ $r->num_siege }}</div>
                    <div style="margin: 12px 0 6px;">
                        <img src="{{ $r->qrCodeDataUri(170) }}" width="170" height="170">
                    </div>
                    <div class="label" style="letter-spacing: .5px;">{{ __('À scanner à l\'embarquement') }}</div>
                    <div class="val" style="font-size: 15px; margin-top: 8px;">{{ __($r->villeDepart?->ville) }} » {{ __($r->villeArrivee?->ville) }}</div>
                    <div class="date">{{ $d->format('d/m/Y') }} · {{ $d->format('H:i') }}</div>
                </td>
            </tr>
        </table>

        <table class="foot" width="100%">
            <tr>
                <td>{{ __('Présentez ce billet (imprimé ou sur votre téléphone) 15 minutes avant le départ, avec une pièce d\'identité.') }}</td>
                <td align="right" style="white-space: nowrap;">{{ config('safar.contact.telephone') }} · {{ config('safar.contact.email') }}</td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
