<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\ReservationMail;
use App\Models\Reservation;
use App\Support\Cmi;
use Illuminate\Http\Request;

/**
 * Card payment with CMI. The reservation (statut en_attente, seat held) is confirmed only by the
 * server-to-server callback signed by CMI; the browser redirects (ok / fail) just show the result.
 */
class PaymentController extends Controller
{
    /** Auto-submitted form that sends the client to the CMI payment page. */
    public function start(Reservation $reservation)
    {
        abort_unless($reservation->user_id === auth()->id(), 403);
        abort_unless(Cmi::available(), 404);

        if ($reservation->statut !== Reservation::EN_ATTENTE) {
            return $this->toOrder($reservation);
        }

        // local PC without CMI keys: our own test page stands in for the bank's page
        if (Cmi::testMode()) {
            $billets = $reservation->commandeBillets()->where('statut', Reservation::EN_ATTENTE)->values();

            return view('client.payment.test', [
                'reservation' => $reservation->loadMissing(['villeDepart', 'villeArrivee']),
                'billets' => $billets,
                'montant' => Cmi::amount($reservation),
            ]);
        }

        return view('client.payment.redirect', [
            'reservation' => $reservation,
            'gateway' => Cmi::gatewayUrl(),
            'fields' => Cmi::fields($reservation),
        ]);
    }

    /** Local test page answer: "pay" confirms the whole order, "refuse" releases its seats. Local only. */
    public function test(Request $request, Reservation $reservation)
    {
        abort_unless(Cmi::testMode(), 404);
        abort_unless($reservation->user_id === auth()->id(), 403);

        $pending = $reservation->commandeBillets()->where('statut', Reservation::EN_ATTENTE)->values();
        if ($pending->isEmpty()) {
            return $this->toOrder($reservation);
        }

        if ($request->input('resultat') !== 'ok') {
            $pending->each->cancel('systeme');

            return redirect()->route('client.reservations.show', $reservation->voyage_id)
                ->with('error', __('Le paiement n\'a pas abouti. Aucun montant n\'a été débité ; vous pouvez réessayer.'));
        }

        $pending->each(fn ($billet) => $billet->markPaid('TEST-' . now()->format('His')));
        ReservationMail::sendTo($pending, 'confirmee');

        return $this->toOrder($reservation)->with('success', trans_choice('{1} Paiement accepté ! Votre billet vous a aussi été envoyé par e-mail.|[2,*] Paiement accepté ! Vos :count billets vous ont aussi été envoyés par e-mail.', $pending->count()));
    }

    /** Page with every ticket of the order (or the ticket itself for old reservations without order). */
    private function toOrder(Reservation $reservation)
    {
        return $reservation->commande
            ? redirect()->route('client.commande.show', $reservation->commande)
            : redirect()->route('ticket.show', $reservation->id);
    }

    /** Server-to-server notification from CMI. Answers "ACTION=POSTAUTH" to accept the payment. */
    public function callback(Request $request)
    {
        $data = $request->all();

        if (! Cmi::enabled() || ! Cmi::verify($data)) {
            return response('FAILURE', 200)->header('Content-Type', 'text/plain');
        }

        $reservation = $this->reservationFromOrder((string) ($data['oid'] ?? ''));
        $approved = ($data['ProcReturnCode'] ?? null) === '00' && strtolower((string) ($data['Response'] ?? '')) === 'approved';

        if (! $reservation || ! $approved) {
            return response('APPROVED', 200)->header('Content-Type', 'text/plain'); // acknowledged, not accepted
        }

        // the payment covers the whole order (every seat booked together)
        if (number_format((float) ($data['amount'] ?? 0), 2, '.', '') !== Cmi::amount($reservation)) {
            return response('FAILURE', 200)->header('Content-Type', 'text/plain');
        }

        $ref = $data['TransId'] ?? $data['oid'];
        $confirmes = collect();
        foreach ($reservation->commandeBillets() as $billet) {
            if ($billet->isPaid()) {
                continue;
            }
            if ($billet->isCancelled()) {
                // paid after the seat hold expired: keep it cancelled, flag the refund
                $billet->forceFill(['paye_le' => now(), 'paiement_ref' => $ref])->save();
            } else {
                $billet->markPaid($ref);
                $confirmes->push($billet);
            }
        }
        if ($confirmes->isNotEmpty()) {
            ReservationMail::sendTo($confirmes, 'confirmee');
        }

        return response('ACTION=POSTAUTH', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * The client comes back after paying (CMI posts from its own site, so no session cookie: no login check,
     * and nothing is changed here; the confirmation itself comes from the callback).
     */
    public function ok(Reservation $reservation)
    {
        $reservation->refresh();

        if ($reservation->isPaid() && ! $reservation->isCancelled()) {
            return $this->toOrder($reservation)
                ->with('success', __('Paiement accepté ! Votre billet vous a aussi été envoyé par e-mail.'));
        }

        return $this->toOrder($reservation)
            ->with('success', __('Paiement en cours de confirmation par la banque. Votre billet sera confirmé dans quelques instants.'));
    }

    /** Payment refused or abandoned: the seat is released (only on data signed by CMI; otherwise the hold simply expires). */
    public function fail(Request $request, Reservation $reservation)
    {
        if ($reservation->statut === Reservation::EN_ATTENTE && Cmi::enabled() && Cmi::verify($request->all())) {
            // every seat of the order is released together
            $reservation->commandeBillets()->where('statut', Reservation::EN_ATTENTE)->each->cancel('systeme');
        }

        return redirect()->route('client.reservations.show', $reservation->voyage_id)
            ->with('error', __('Le paiement n\'a pas abouti. Aucun montant n\'a été débité ; vous pouvez réessayer.'));
    }

    private function reservationFromOrder(string $orderId): ?Reservation
    {
        if (! preg_match('/^BL(\d+)-/', $orderId, $m)) {
            return null;
        }

        return Reservation::withoutGlobalScope('active')->find((int) $m[1]);
    }
}
