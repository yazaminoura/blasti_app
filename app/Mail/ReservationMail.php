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
 */
class ReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public const TYPES = ['confirmee', 'rappel', 'annulee'];

    public function __construct(public Reservation $reservation, public string $type)
    {
        $this->reservation->loadMissing(['user', 'villeDepart', 'villeArrivee', 'modeReglement', 'autocar.societe']);
    }

    /** Sends without ever breaking the page (a mail server problem is logged, the booking stays valid). */
    public static function sendTo(Reservation $reservation, string $type): bool
    {
        if (! $reservation->user?->email) {
            return false;
        }
        try {
            Mail::to($reservation->user->email)->send(new self($reservation, $type));

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
            default => __('Votre billet :trajet est confirmé | :brand', ['trajet' => $trajet, 'brand' => $brand]),
            },
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reservation', with: [
            'r' => $this->reservation,
            'type' => $this->type,
            'couleur' => config('safar.couleur'),
        ]);
    }

    public function attachments(): array
    {
        if ($this->type === 'annulee') {
            return [];
        }

        $reservation = $this->reservation;

        return [
            Attachment::fromData(
                fn () => Pdf::loadView('client.reservations.ticket-pdf', ['reservation' => $reservation])->setPaper('a5', 'landscape')->output(),
                'billet-' . $reservation->id . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
