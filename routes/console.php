<?php

use App\Mail\ReservationMail;
use App\Models\Reservation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder e-mail (with the PDF ticket) for every trip leaving tomorrow; each ticket gets it once.
Artisan::command('reservations:rappels', function () {
    $sent = 0;
    Reservation::where('statut', Reservation::CONFIRMEE)
        ->whereDate('date_depart', today()->addDay())
        ->whereNull('rappel_envoye_le')
        ->with('user')
        ->each(function (Reservation $reservation) use (&$sent) {
            if (ReservationMail::sendTo($reservation, 'rappel')) {
                $reservation->forceFill(['rappel_envoye_le' => now()])->save();
                $sent++;
            }
        });
    $this->info("{$sent} rappel(s) envoyé(s).");
})->purpose('Envoie le rappel de départ aux voyageurs de demain');

// Card payments abandoned on the CMI page: release the held seats.
Artisan::command('reservations:expirer', function () {
    $this->info(Reservation::expirePendingPayments() . ' réservation(s) en attente de paiement libérée(s).');
})->purpose('Libère les sièges des paiements par carte non aboutis');

// Unpaid tickets (pay later, not agency), config safar.confirmation:
//  1. `demande_heures` after booking, still unpaid: e-mail "pay, or confirm you are coming" (signed link, no login)
//  2. cancelled `limite_heures` after the e-mail without payment nor answer,
//     or `paiement_heures` after the e-mail if the client confirmed but still did not pay
//  Never after departure: then the controller's scan decides (paid at the door, or no-show).
Artisan::command('reservations:presence', function () {
    if (! config('safar.confirmation.active')) {
        return $this->info('Confirmation de présence désactivée.');
    }
    $asked = $cancelled = 0;

    Reservation::where('statut', Reservation::CONFIRMEE)
        ->whereNull('paye_le')
        ->whereDate('date_depart', '>=', today())
        ->whereHas('modeReglement', fn ($q) => $q->where('en_ligne', false)->where('en_agence', false))
        ->with('user')
        ->each(function (Reservation $r) use (&$asked, &$cancelled) {
            if ($r->departAt()->isPast()) {
                return; // bus gone: the controller's scan decides (no-show)
            }
            if ($r->confirmation_demandee_le === null) {
                if ($r->presenceAskedAt()->lte(now()) && ReservationMail::sendTo($r, 'presence')) {
                    $r->forceFill(['confirmation_demandee_le' => now()])->save();
                    $asked++;
                }
            } elseif (($due = $r->cancellationDue()) && $due->lte(now())) {
                $r->cancel('systeme');
                ReservationMail::sendTo($r, 'sans_confirmation');
                $cancelled++;
            }
        });

    $this->info("{$asked} e-mail(s) « payez ou confirmez » envoyé(s), {$cancelled} billet(s) non payé(s) annulé(s).");
})->purpose('Billets non payés : e-mail 24 h après la réservation, annulation sans paiement');

// The day after the trip: "how was it?" e-mail with the review button (once per ticket, trips of the last 3 days)
Artisan::command('reservations:avis', function () {
    $sent = 0;
    Reservation::where('statut', Reservation::CONFIRMEE)
        ->whereNull('avis_demande_le')
        ->whereDate('date_arrivee', '>=', today()->subDays(3))
        ->whereDate('date_arrivee', '<=', today())
        ->whereDoesntHave('avis')
        ->with('user')
        ->each(function (Reservation $r) use (&$sent) {
            if ($r->arriveeAt()->isPast() && ReservationMail::sendTo($r, 'avis')) {
                $r->forceFill(['avis_demande_le' => now()])->save();
                $sent++;
            }
        });
    $this->info("{$sent} demande(s) d'avis envoyée(s).");
})->purpose('Demande un avis aux voyageurs après leur trajet');

// "Pay at an agency": the order code must be paid within config safar.agence_delai_heures, else cancelled
Artisan::command('reservations:agence', function () {
    $delai = (int) config('safar.agence_delai_heures');
    $cancelled = 0;
    Reservation::where('statut', Reservation::CONFIRMEE)->whereNull('paye_le')
        ->whereHas('modeReglement', fn ($q) => $q->where('en_agence', true))
        ->where('created_at', '<', now()->subHours($delai))
        ->with('user')
        ->each(function (Reservation $r) use (&$cancelled) {
            $r->cancel('systeme');
            ReservationMail::sendTo($r, 'annulee');
            $cancelled++;
        });
    $this->info("{$cancelled} billet(s) non payé(s) en agence annulé(s).");
})->purpose('Annule les billets à payer en agence non payés à temps');

// Needs the Laravel scheduler on the server: "* * * * * php artisan schedule:run" (cron)
Schedule::command('reservations:rappels')->dailyAt('18:00');
Schedule::command('reservations:expirer')->everyFiveMinutes();
Schedule::command('reservations:presence')->everyFifteenMinutes();
Schedule::command('reservations:avis')->dailyAt('10:00');
Schedule::command('reservations:agence')->everyFifteenMinutes();
