<?php

use App\Mail\ReservationMail;
use App\Models\Reservation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// New site: first super admin account (asks the password, never written in a file). Also resets an existing one.
Artisan::command('blasti:admin {email?} {--name=}', function () {
    $email = $this->argument('email') ?: $this->ask('E-mail');
    $name = $this->option('name') ?: $this->ask('Nom', 'Administrateur');
    $password = $this->secret('Mot de passe (8 caractères minimum)');

    $errors = \Illuminate\Support\Facades\Validator::make(['email' => $email, 'password' => $password, 'password_confirmation' => $this->secret('Confirmez le mot de passe')], [
        'email' => ['required', 'email'], 'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
    ])->errors();
    if ($errors->isNotEmpty()) {
        foreach ($errors->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = \App\Models\User::firstOrNew(['email' => strtolower($email)]);
    $user->forceFill([
        'name' => $user->name ?: $name, 'password' => \Illuminate\Support\Facades\Hash::make($password),
        'isadmin' => 1, 'societe_id' => null, 'email_verified_at' => $user->email_verified_at ?? now(), 'desactive_le' => null,
    ])->save();
    $user->roles()->detach(); // super admin = admin without role

    $this->info(($user->wasRecentlyCreated ? 'Super admin créé : ' : 'Super admin mis à jour : ') . $user->email);
})->purpose('Crée (ou remet) le compte super admin du site');

// Reminder e-mail (with the PDF tickets) for every trip leaving tomorrow; each order gets it once.
Artisan::command('reservations:rappels', function () {
    $sent = 0;
    Reservation::parCommande(Reservation::where('statut', Reservation::CONFIRMEE)
        ->whereDate('date_depart', today()->addDay())
        ->whereNull('rappel_envoye_le'))
        ->each(function ($billets) use (&$sent) {
            if (ReservationMail::sendTo($billets, 'rappel', attendre: true)) {
                Reservation::whereKey($billets->modelKeys())->update(['rappel_envoye_le' => now()]);
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
//  One e-mail per order; its link confirms every seat of the order.
Artisan::command('reservations:presence', function () {
    if (! config('safar.confirmation.active')) {
        return $this->info('Confirmation de présence désactivée.');
    }
    $asked = $cancelled = 0;

    Reservation::parCommande(Reservation::where('statut', Reservation::CONFIRMEE)
        ->whereNull('paye_le')
        ->whereDate('date_depart', '>=', today())
        ->whereHas('modeReglement', fn ($q) => $q->where('en_ligne', false)->where('en_agence', false)))
        ->each(function ($billets) use (&$asked, &$cancelled) {
            $r = $billets->first();
            if ($r->departAt()->isPast()) {
                return; // bus gone: the controller's scan decides (no-show)
            }
            $nonDemandes = $billets->whereNull('confirmation_demandee_le');
            if ($nonDemandes->isNotEmpty()) {
                if ($r->presenceAskedAt()->lte(now()) && ReservationMail::sendTo($billets, 'presence', attendre: true)) {
                    Reservation::whereKey($nonDemandes->modelKeys())->update(['confirmation_demandee_le' => now()]);
                    $asked++;
                }

                return;
            }
            $dus = $billets->filter(fn (Reservation $b) => ($due = $b->cancellationDue()) && $due->lte(now()));
            if ($dus->isNotEmpty()) {
                $dus->each->cancel('systeme');
                ReservationMail::sendTo($dus, 'sans_confirmation');
                $cancelled += $dus->count();
            }
        });

    $this->info("{$asked} e-mail(s) « payez ou confirmez » envoyé(s), {$cancelled} billet(s) non payé(s) annulé(s).");
})->purpose('Billets non payés : e-mail 24 h après la réservation, annulation sans paiement');

// The day after the trip: "how was it?" e-mail with the review button (once per order, trips of the last 3 days).
// Only to people who travelled: boarded, or paid on a bus whose controller did not use the scanner.
Artisan::command('reservations:avis', function () {
    $sent = 0;
    $scanne = [];
    Reservation::parCommande(Reservation::where('statut', Reservation::CONFIRMEE)
        ->whereNull('avis_demande_le')
        ->whereDate('date_arrivee', '>=', today()->subDays(3))
        ->whereDate('date_arrivee', '<=', today())
        ->whereDoesntHave('avis'))
        ->each(function ($billets) use (&$sent, &$scanne) {
            $r = $billets->first();
            if (! $r->arriveeAt()->isPast()) {
                return;
            }
            $busScanne = $scanne[$r->voyage_id] ??= Reservation::where('voyage_id', $r->voyage_id)->whereNotNull('embarque_le')->exists();
            $voyageurs = $billets->filter(fn (Reservation $b) => $b->isBoarded() || ($b->isPaid() && ! $busScanne));
            if ($voyageurs->isNotEmpty() && ReservationMail::sendTo($voyageurs, 'avis', attendre: true)) {
                $sent++;
            }
            // asked (or skipped: no-show) once for good
            Reservation::whereKey($billets->modelKeys())->update(['avis_demande_le' => now()]);
        });
    $this->info("{$sent} demande(s) d'avis envoyée(s).");
})->purpose('Demande un avis aux voyageurs après leur trajet');

// "Pay at an agency": the order code must be paid within config safar.agence_delai_heures, else cancelled.
// Never after departure: then it was a no-show, kept as such (User::absences).
Artisan::command('reservations:agence', function () {
    $delai = (int) config('safar.agence_delai_heures');
    $cancelled = 0;
    Reservation::parCommande(Reservation::where('statut', Reservation::CONFIRMEE)->whereNull('paye_le')
        ->whereHas('modeReglement', fn ($q) => $q->where('en_agence', true))
        ->where('created_at', '<', now()->subHours($delai)))
        ->each(function ($billets) use (&$cancelled) {
            $billets = $billets->reject(fn (Reservation $r) => $r->departAt()->isPast());
            if ($billets->isEmpty()) {
                return;
            }
            $billets->each->cancel('systeme');
            ReservationMail::sendTo($billets, 'annulee');
            $cancelled += $billets->count();
        });
    $this->info("{$cancelled} billet(s) non payé(s) en agence annulé(s).");
})->purpose('Annule les billets à payer en agence non payés à temps');

// Needs the Laravel scheduler on the server: "* * * * * php artisan schedule:run" (cron).
// withoutOverlapping: a slow run (SMTP) is never doubled by the next one (double e-mails / cancellations).
Schedule::command('reservations:rappels')->dailyAt('18:00')->withoutOverlapping();
Schedule::command('reservations:expirer')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('reservations:presence')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('reservations:avis')->dailyAt('10:00')->withoutOverlapping();
Schedule::command('reservations:agence')->everyFifteenMinutes()->withoutOverlapping();
