<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0; padding:0; background:#f3f5f9; font-family: Arial, Helvetica, sans-serif; color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f9; padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:12px; overflow:hidden;">
            <tr>
                <td style="background:{{ $couleur }}; padding:22px 28px; color:#ffffff;">
                    <div style="font-size:13px; opacity:.85; letter-spacing:.08em; text-transform:uppercase;">{{ config('safar.nom') }}</div>
                    <div style="font-size:21px; font-weight:bold; margin-top:4px;">{{ __('Bonne nouvelle : une place s\'est libérée !') }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:24px 28px;">
                    <p style="margin:0 0 16px; line-height:1.5;">{{ __('Le bus que vous attendiez a de nouveau une place libre. Les places partent vite : réservez maintenant.') }}</p>
                    <div style="background:#f6f8fc; border-radius:10px; padding:14px 16px;">
                        <div style="font-size:19px; font-weight:bold; color:{{ $couleur }};">{{ __($depart->ville?->ville) }} → {{ __($arrivee->ville?->ville) }}</div>
                        <div style="margin-top:6px; font-size:14px;">{{ ucfirst($depart->passage_at->locale(app()->getLocale())->isoFormat('dddd D MMMM')) }} · {{ $depart->passage_at->format('H:i') }}</div>
                    </div>
                    <p style="margin:22px 0 0; text-align:center;">
                        <a href="{{ $lien }}" style="display:inline-block; background:{{ $couleur }}; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; font-weight:bold;">{{ __('Choisir mon siège') }}</a>
                    </p>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>
