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

// Unpaid tickets (pay at boarding), config safar.confirmation:
//  1. `demande_heures` before boarding: e-mail "confirm you are coming" (signed link, no login needed)
//  2. `limite_heures` before boarding, still no answer: the ticket is cancelled, the seat goes back on sale
Artisan::command('reservations:presence', function () {
    if (! config('safar.confirmation.active')) {
        return $this->info('Confirmation de présence désactivée.');
    }
    $demande = (int) config('safar.confirmation.demande_heures');
    $limite = (int) config('safar.confirmation.limite_heures');
    $asked = $cancelled = 0;

    Reservation::where('statut', Reservation::CONFIRMEE)
        ->whereNull('paye_le')->whereNull('presence_confirmee_le')
        ->whereDate('date_depart', '<=', now()->addHours($demande)->toDateString())
        ->with('user')
        ->each(function (Reservation $r) use ($demande, $limite, &$asked, &$cancelled) {
            $hoursLeft = now()->diffInMinutes($r->departAt(), false) / 60;
            if ($hoursLeft <= 0) {
                return; // bus gone: the controller's scan decides (no-show)
            }
            if ($r->confirmation_demandee_le === null && $hoursLeft <= $demande && $hoursLeft > $limite) {
                if (ReservationMail::sendTo($r, 'presence')) {
                    $r->forceFill(['confirmation_demandee_le' => now()])->save();
                    $asked++;
                }
            } elseif ($r->confirmation_demandee_le !== null && $hoursLeft <= $limite) {
                $r->cancel('systeme');
                ReservationMail::sendTo($r, 'sans_confirmation');
                $cancelled++;
            }
        });

    $this->info("{$asked} demande(s) de confirmation envoyée(s), {$cancelled} billet(s) non confirmé(s) annulé(s).");
})->purpose('Demande aux voyageurs non payés de confirmer leur présence, annule sans réponse');

// Needs the Laravel scheduler on the server: "* * * * * php artisan schedule:run" (cron)
Schedule::command('reservations:rappels')->dailyAt('18:00');
Schedule::command('reservations:expirer')->everyFiveMinutes();
Schedule::command('reservations:presence')->everyFifteenMinutes();
