<?php

namespace App\Support;

use App\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * "Envoyer sur WhatsApp" button: opens WhatsApp (app or web) with a ready message — trip, seats and a link
 * to each ticket's PDF. The client picks the contact (himself, his family...). No WhatsApp Business account needed.
 */
class WhatsApp
{
    /** Signed link to the PDF of a ticket, opens without logging in, valid until 2 days after the trip. */
    public static function lienPdf(Reservation $billet): string
    {
        return URL::temporarySignedRoute('ticket.pdf.partage', $billet->departAt()->addDays(2), $billet);
    }

    /** https://wa.me/?text=... for one ticket or every ticket of an order (cancelled ones left out). */
    public static function lien(Reservation|Collection $billets): string
    {
        $billets = ($billets instanceof Reservation ? collect([$billets]) : $billets)->reject->isCancelled()->values();
        $premier = $billets->first();
        if (! $premier) {
            return 'https://wa.me/';
        }
        $d = $premier->departAt();

        $lignes = [
            '🎫 ' . __('Billet :brand', ['brand' => config('safar.nom')]),
            __($premier->villeDepart?->ville) . ' → ' . __($premier->villeArrivee?->ville),
            '📅 ' . ucfirst($d->translatedFormat('l d F')) . ' · ' . $d->format('H:i'),
            '🚌 ' . trim(($premier->autocar?->societe?->raison_social ?? '') . ' ' . ($premier->autocar?->matricule ?? '')),
            '',
        ];
        foreach ($billets as $billet) {
            $lignes[] = '💺 ' . __('Siège :num', ['num' => $billet->num_siege]) . ' · ' . $billet->passager() . "\n" . self::lienPdf($billet);
        }
        $reste = $billets->sum(fn ($b) => $b->resteAPayer());
        if ($reste > 0) {
            $lignes[] = '';
            $lignes[] = __('À payer au contrôleur : :montant DH', ['montant' => number_format($reste, 2, ',', ' ')]);
        }

        return 'https://wa.me/?text=' . rawurlencode(implode("\n", $lignes));
    }
}
