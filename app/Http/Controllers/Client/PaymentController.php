<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\ReservationMail;
use App\Models\Reservation;
use App\Models\Voyage;
use App\Support\Cmi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            return $this->repondre($data, 'FAILURE', 'signature invalide');
        }

        $reservation = $this->reservationFromOrder((string) ($data['oid'] ?? ''));
        $approved = ($data['ProcReturnCode'] ?? null) === '00' && strtolower((string) ($data['Response'] ?? '')) === 'approved';

        if (! $reservation || ! $approved) {
            // acknowledged, not accepted
            return $this->repondre($data, 'APPROVED', $reservation ? 'paiement refusé par la banque' : 'commande inconnue');
        }

        $ref = $data['TransId'] ?? $data['oid'];
        $paid = Cmi::format((float) ($data['amount'] ?? 0));

        // locks: the voyage (seats), then the order's tickets, so the hold expiry cannot cancel them meanwhile
        $confirmes = DB::transaction(function () use ($reservation, $ref, $paid) {
            $voyage = $reservation->voyage_id ? Voyage::with('arrets')->lockForUpdate()->find($reservation->voyage_id) : null;
            $billets = $reservation->commande
                ? Reservation::withoutGlobalScope('active')->where('commande', $reservation->commande)->orderBy('num_siege')->lockForUpdate()->get()
                : Reservation::withoutGlobalScope('active')->whereKey($reservation->id)->lockForUpdate()->get();
            $billets = Cmi::billetsPayes($billets);

            // nothing left to pay (CMI resends the callback), or not the amount of these seats
            if ($billets->isEmpty() || $paid !== Cmi::format($billets->sum(fn ($b) => $b->total()))) {
                return null;
            }

            $confirmes = collect();
            foreach ($billets as $billet) {
                // hold expired while paying: back if the seat is still free, else cancelled + paid = to refund
                if ($billet->isCancelled() && ! $billet->revivre($voyage)) {
                    $billet->forceFill(['paye_le' => now(), 'montant_paye' => $billet->total(), 'paiement_ref' => $ref])->save();
                    continue;
                }
                $billet->markPaid($ref);
                $confirmes->push($billet);
            }

            return $confirmes;
        });

        if ($confirmes === null) {
            // a resent callback of a payment already recorded is accepted again; anything else is refused
            return Reservation::withoutGlobalScope('active')->where('paiement_ref', $ref)->exists()
                ? $this->repondre($data, 'ACTION=POSTAUTH', 'déjà enregistré (rappel renvoyé)')
                : $this->repondre($data, 'FAILURE', 'montant différent de la commande, ou rien à payer');
        }
        if ($confirmes->isNotEmpty()) {
            ReservationMail::sendTo($confirmes, 'confirmee');
        }

        return $this->repondre($data, 'ACTION=POSTAUTH', $confirmes->isEmpty()
            ? 'payé après expiration, siège revendu : à rembourser'
            : 'payé : billets ' . $confirmes->pluck('id')->join(', '));
    }

    /** Answer to CMI + one line in storage/logs/payments-*.log (order, transaction, amount, result; no card data). */
    private function repondre(array $data, string $reponse, string $resultat)
    {
        \Illuminate\Support\Facades\Log::channel('payments')->info('CMI callback', [
            'oid' => $data['oid'] ?? null, 'TransId' => $data['TransId'] ?? null, 'amount' => $data['amount'] ?? null,
            'ProcReturnCode' => $data['ProcReturnCode'] ?? null, 'Response' => $data['Response'] ?? null,
            'ip' => request()->ip(), 'reponse' => $reponse, 'resultat' => $resultat,
        ]);

        return response($reponse, 200)->header('Content-Type', 'text/plain');
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
