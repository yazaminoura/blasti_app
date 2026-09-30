<?php

namespace App\Mail;

use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Emails sent to the client about a reservation:
 *  - confirmee: right after booking (or after the card payment), with the PDF ticket
 *  - rappel:    the day before departure, with the PDF ticket
 *  - annulee:   when the client or the admin cancels
 *  - modifiee:  the client moved the ticket to another departure, with the new PDF ticket
 *  - presence:  unpaid ticket, "confirm you are coming" button (routes/console.php reservations:presence)
 *  - sans_confirmation: unpaid ticket cancelled because the presence was not confirmed in time
 */
class ReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public const TYPES = ['confirmee', 'rappel', 'annulee', 'modifiee', 'presence', 'sans_confirmation', 'avis'];

    /** Every ticket covered by this e-mail (several seats booked together = one e-mail, one PDF page each). */
    public \Illuminate\Support\Collection $billets;

    public function __construct(public Reservation $reservation, public string $type, ?\Illuminate\Support\Collection $billets = null)
    {
        $this->billets = ($billets ?? collect([$reservation]))->values();
        $this->billets->each->loadMissing(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe']);
    }

    /**
     * Sends without ever breaking the page (a mail server problem is logged, the booking stays valid).
     * $reservation: one ticket, or the tickets of one order (same client, same trip).
     */
    public static function sendTo(Reservation|\Illuminate\Support\Collection $reservation, string $type): bool
    {
        $billets = $reservation instanceof Reservation ? collect([$reservation]) : $reservation->values();
        $reservation = $billets->first();

        if (! $reservation?->user?->email) {
            return false;
        }
        try {
            Mail::to($reservation->user->email)->send(new self($reservation, $type, $billets));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function envelope(): Envelope
    {
        $trajet = __($this->reservation->villeDepart?->ville) . ' → ' . __($this->reservation->villeArrivee?->ville);
        $brand = config('safar.nom');

        return new Envelope(
            // "Répondez à cet e-mail": answers go to the public contact address, not the technical sender
            replyTo: [new \Illuminate\Mail\Mailables\Address(config('safar.contact.email'), $brand)],
            subject: match ($this->type) {
            'rappel' => __('Rappel : votre bus :trajet part demain | :brand', ['trajet' => $trajet, 'brand' => $brand]),
            'annulee' => __('Billet #:id annulé | :brand', ['id' => $this->reservation->id, 'brand' => $brand]),
            'modifiee' => __('Billet #:id modifié : :trajet | :brand', ['id' => $this->reservation->id, 'trajet' => $trajet, 'brand' => $brand]),
            'avis' => __('Comment s\'est passé votre voyage :trajet ? | :brand', ['trajet' => $trajet, 'brand' => $brand]),
            'presence' => __('Action requise : confirmez votre voyage :trajet | :brand', ['trajet' => $trajet, 'brand' => $brand]),
            'sans_confirmation' => __('Billet #:id annulé (présence non confirmée) | :brand', ['id' => $this->reservation->id, 'brand' => $brand]),
            default => $this->billets->count() > 1
                ? __('Vos :count billets :trajet sont confirmés | :brand', ['count' => $this->billets->count(), 'trajet' => $trajet, 'brand' => $brand])
                : __('Votre billet :trajet est confirmé | :brand', ['trajet' => $trajet, 'brand' => $brand]),
            },
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reservation', with: [
            'r' => $this->reservation,
            'billets' => $this->billets,
            'type' => $this->type,
            'couleur' => config('safar.couleur'),
        ]);
    }

    public function attachments(): array
    {
        // only tickets that are still valid get the PDF
        if (in_array($this->type, ['annulee', 'presence', 'sans_confirmation', 'avis'], true)) {
            return [];
        }

        $billets = $this->billets;
        $nom = $billets->count() > 1 ? 'billets-' . ($this->reservation->commande ?? $this->reservation->id) : 'billet-' . $this->reservation->id;

        // one PDF, one page (with its QR code) per seat
        return [
            Attachment::fromData(
                fn () => Pdf::loadView('client.reservations.ticket-pdf', ['reservations' => $billets])->setPaper('a5', 'landscape')->output(),
                $nom . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
