<?php

namespace App\Mail;

use App\Models\Voyage;
use App\Models\VoyageArret;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A seat freed up on a bus that was full (AlertePlace): first come, first served. */
class PlaceLibreMail extends Mailable
{
    public function __construct(public Voyage $voyage, public VoyageArret $depart, public VoyageArret $arrivee)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Une place s\'est libérée : :trajet | :brand', [
            'trajet' => __($this->depart->ville?->ville) . ' → ' . __($this->arrivee->ville?->ville),
            'brand' => config('safar.nom'),
        ]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.place-libre', with: [
            'lien' => route('client.reservations.show', ['voyage' => $this->voyage, 'de' => $this->depart->id, 'a' => $this->arrivee->id]),
            'couleur' => config('safar.couleur'),
        ]);
    }
}
