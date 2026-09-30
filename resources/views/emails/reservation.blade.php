@php
    $depart = \Carbon\Carbon::parse($r->date_depart)->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY');
    $heure = \Carbon\Carbon::parse($r->heure_depart)->format('H:i');
    $arrivee = __(':date à :heure', ['date' => \Carbon\Carbon::parse($r->date_arrivee)->locale(app()->getLocale())->isoFormat('D MMMM'), 'heure' => \Carbon\Carbon::parse($r->heure_arrivee)->format('H:i')]);
    $billets = collect($billets ?? [$r]);
    $total = number_format($billets->sum(fn ($b) => $b->total()), 2, ',', ' ') . ' DH';
    [$title, $intro] = match ($type) {
        'rappel' => [__('Votre départ, c\'est demain'), __('Petit rappel : votre bus part demain. Présentez-vous au moins 15 minutes avant le départ avec votre billet (en pièce jointe).')],
        'annulee' => [__('Votre billet a été annulé'), __('Votre réservation a bien été annulée et le siège a été libéré.') . ($r->needsRefund() ? ' ' . __('Remboursement prévu : :montant DH (frais d\'annulation : :frais DH). Notre équipe le traite rapidement.', ['montant' => number_format($r->amountToRefund(), 2, ',', ' '), 'frais' => number_format((float) $r->frais_annulation, 2, ',', ' ')]) : '')],
        'avis' => [__('Comment s\'est passé votre voyage ?'), __('Merci d\'avoir voyagé avec nous ! Votre avis sur la compagnie (ponctualité, confort, accueil) aide les autres voyageurs à choisir. Cela prend 30 secondes.')],
        'presence' => [__('Confirmez que vous voyagez'), __('Votre billet n\'est pas encore payé (paiement à l\'embarquement). Pour garder votre siège, confirmez que vous serez bien là en cliquant sur le bouton ci-dessous avant le :date à :heure. Sans confirmation, le billet sera annulé et le siège remis en vente.', ['date' => $r->presenceDeadline()->translatedFormat('d M'), 'heure' => $r->presenceDeadline()->format('H:i')])],
        'sans_confirmation' => [__('Votre billet a été annulé'), __('Vous n\'avez pas confirmé votre présence à temps : ce billet non payé a été annulé et le siège remis en vente. Vous pouvez réserver à nouveau si des places sont libres.')],
        'modifiee' => [__('Votre billet a été modifié'), __('Votre billet a bien été déplacé sur le départ ci-dessous. Votre nouveau billet est en pièce jointe : l\'ancien n\'est plus valable.') . ($r->isPaid() && $r->resteAPayer() > 0 ? ' ' . __('Supplément à régler à l\'embarquement : :montant DH.', ['montant' => number_format($r->resteAPayer(), 2, ',', ' ')]) : '')],
        default => $billets->count() > 1
            ? [__('Vos billets sont confirmés'), __('Merci pour votre réservation ! Vos billets sont en pièce jointe (une page et un QR code par siège) : présentez-les à l\'embarquement, sur votre téléphone ou imprimés.')]
            : [__('Votre billet est confirmé'), __('Merci pour votre réservation ! Votre billet est en pièce jointe, avec son QR code : présentez-le à l\'embarquement, sur votre téléphone ou imprimé.')],
    };
@endphp
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
                    <div style="font-size:21px; font-weight:bold; margin-top:4px;">{{ $title }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:24px 28px;">
                    <p style="margin:0 0 16px;">{{ __('Bonjour :name,', ['name' => $r->user?->name]) }}</p>
                    <p style="margin:0 0 20px; line-height:1.5;">{{ $intro }}</p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fc; border-radius:10px; padding:16px;">
                        <tr><td style="padding:14px 16px;">
                            <div style="font-size:19px; font-weight:bold; color:{{ $couleur }};">{{ __($r->villeDepart?->ville) }} → {{ __($r->villeArrivee?->ville) }}</div>
                            <div style="margin-top:6px; font-size:14px;">{{ __('Départ :') }} <strong>{{ __(':date à :heure', ['date' => ucfirst($depart), 'heure' => $heure]) }}</strong></div>
                            <div style="font-size:14px; color:#6b7280;">{{ __('Arrivée prévue :') }} {{ $arrivee }}</div>
                            <div style="margin-top:10px; font-size:14px;">
                                @if ($billets->count() > 1)
                                    {{ __(':count billets', ['count' => $billets->count()]) }} <strong>{{ $r->commande }}</strong> · {{ __('Sièges') }} <strong>{{ $billets->map(fn ($b) => $b->num_siege . ' (' . $b->passager() . ')')->join(', ') }}</strong>
                                @else
                                    {{ __('Billet') }} <strong>#{{ $r->id }}</strong> · {{ __('Siège') }} <strong>{{ __('N° :num', ['num' => $r->num_siege]) }}</strong>
                                @endif
                                @if ($r->autocar?->societe) · {{ $r->autocar->societe->raison_social }}@endif
                            </div>
                            <div style="font-size:14px;">{{ __('Total :') }} <strong>{{ $total }}</strong> · {{ $r->statusBadge()[0] }}</div>
                        </td></tr>
                    </table>

                    @if ($type === 'avis')
                        <p style="margin:22px 0 0; text-align:center;">
                            <a href="{{ route('client.avis.create', $r->id) }}" style="display:inline-block; background:#f59e0b; color:#ffffff; text-decoration:none; padding:14px 26px; border-radius:8px; font-weight:bold; font-size:16px;">★ {{ __('Donner mon avis') }}</a>
                        </p>
                    @elseif ($type === 'presence')
                        <p style="margin:22px 0 0; text-align:center;">
                            <a href="{{ $r->presenceUrl() }}" style="display:inline-block; background:#16a34a; color:#ffffff; text-decoration:none; padding:14px 26px; border-radius:8px; font-weight:bold; font-size:16px;">{{ __('Je confirme ma présence') }}</a>
                        </p>
                        <p style="margin:14px 0 0; font-size:12px; color:#6b7280; text-align:center;">
                            {{ __('Vous ne voyagez plus ? Ne faites rien : le billet sera annulé automatiquement, sans frais.') }}
                        </p>
                    @elseif ($type === 'sans_confirmation')
                        <p style="margin:22px 0 0; text-align:center;">
                            <a href="{{ route('voyages.list') }}" style="display:inline-block; background:{{ $couleur }}; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; font-weight:bold;">{{ __('Voir les départs') }}</a>
                        </p>
                    @elseif ($type !== 'annulee')
                        <p style="margin:22px 0 0; text-align:center;">
                            <a href="{{ route('ticket.show', $r->id) }}" style="display:inline-block; background:{{ $couleur }}; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:8px; font-weight:bold;">{{ __('Voir mon billet') }}</a>
                        </p>
                        @if ($r->canBeCancelledByClient())
                            <p style="margin:16px 0 0; font-size:12px; color:#6b7280; text-align:center;">
                                {{ __('Un empêchement ? Vous pouvez annuler depuis votre espace jusqu\'au départ du bus. Remboursement d\'un billet payé : 100 % plus de 3 jours avant, puis dégressif.') }}
                            </p>
                        @endif
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding:16px 28px; background:#fafbfc; font-size:12px; color:#6b7280;">
                    {{ __('Une question ? Répondez à cet e-mail ou écrivez-nous : :email · :telephone', ['email' => config('safar.contact.email'), 'telephone' => config('safar.contact.telephone')]) }}
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>
