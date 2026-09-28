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

// Needs the Laravel scheduler on the server: "* * * * * php artisan schedule:run" (cron)
Schedule::command('reservations:rappels')->dailyAt('18:00');
Schedule::command('reservations:expirer')->everyFiveMinutes();
