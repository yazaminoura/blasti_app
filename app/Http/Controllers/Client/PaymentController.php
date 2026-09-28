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
        abort_unless(Cmi::enabled(), 404);

        if ($reservation->statut !== Reservation::EN_ATTENTE) {
            return redirect()->route('ticket.show', $reservation->id);
        }

        return view('client.payment.redirect', [
            'reservation' => $reservation,
            'gateway' => Cmi::gatewayUrl(),
            'fields' => Cmi::fields($reservation),
        ]);
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

        $amount = number_format((float) $reservation->prix + (float) $reservation->frais, 2, '.', '');
        if (number_format((float) ($data['amount'] ?? 0), 2, '.', '') !== $amount) {
            return response('FAILURE', 200)->header('Content-Type', 'text/plain');
        }

        if (! $reservation->isPaid()) {
            if ($reservation->isCancelled()) {
                // paid after the seat hold expired: keep it cancelled, flag the refund
                $reservation->forceFill(['paye_le' => now(), 'paiement_ref' => $data['TransId'] ?? $data['oid']])->save();
            } else {
                $reservation->markPaid($data['TransId'] ?? $data['oid']);
                ReservationMail::sendTo($reservation, 'confirmee');
            }
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
            return redirect()->route('ticket.show', $reservation->id)
                ->with('success', __('Paiement accepté ! Votre billet vous a aussi été envoyé par e-mail.'));
        }

        return redirect()->route('ticket.show', $reservation->id)
            ->with('success', __('Paiement en cours de confirmation par la banque. Votre billet sera confirmé dans quelques instants.'));
    }

    /** Payment refused or abandoned: the seat is released (only on data signed by CMI; otherwise the hold simply expires). */
    public function fail(Request $request, Reservation $reservation)
    {
        if ($reservation->statut === Reservation::EN_ATTENTE && Cmi::enabled() && Cmi::verify($request->all())) {
            $reservation->cancel('systeme');
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
