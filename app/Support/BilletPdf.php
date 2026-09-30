<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * The PDF ticket (client.reservations.ticket-pdf), in the language of the page: in Arabic the text is joined
 * and written right to left (ArabePdf) with a font that has Arabic letters (DejaVu Sans).
 */
class BilletPdf
{
    /** @param  \App\Models\Reservation|Collection  $billets */
    public static function make($billets): \Barryvdh\DomPDF\PDF
    {
        $billets = $billets instanceof Collection ? $billets->values() : collect([$billets]);
        $html = view('client.reservations.ticket-pdf', ['reservations' => $billets])->render();
        if (ArabePdf::contientArabe($html)) {
            $html = ArabePdf::html($html);
        }

        return Pdf::loadHTML($html)->setPaper('a5', 'landscape');
    }
}
