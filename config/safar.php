<?php

// Default brand values. The super admin changes them in Admin > Paramètres > Apparence:
// they are stored in the "parametres" table and copied over these values at boot.
return [
    'nom' => env('SAFAR_NOM', 'Blasti'),
    'couleur' => env('SAFAR_COULEUR', '#0B4FC4'),

    // Colors offered on the Apparence page. All of them keep white text readable (contrast >= 4.5:1).
    // The first three come from the BLASTI logo (its bright orange #F2690D is darkened for readable buttons).
    'palette' => [
        '#0B4FC4' => 'Bleu Blasti',
        '#0A1F5C' => 'Marine Blasti',
        '#C24E05' => 'Orange Blasti',
        '#0F766E' => 'Pétrole',
        '#2563EB' => 'Bleu azur',
        '#4F46E5' => 'Indigo',
        '#15803D' => 'Vert oasis',
        '#B91C1C' => 'Rouge Marrakech',
        '#7C3AED' => 'Violet',
        '#334155' => 'Ardoise',
    ],

    // Demo data loaded by "php artisan migrate" on an empty database (false to start empty)
    'demo' => (bool) env('DEMO_DATA', true),

    /*
     * Cancellation by the client: allowed until the boarding time, never after.
     * Refund of a PAID ticket = % of the price, by hours left before boarding (first matching step wins).
     * Tickets not paid yet (pay at boarding) are cancelled for free: there is nothing to refund.
     * A cancellation by the company (admin) is always refunded 100 %.
     */
    'annulation' => [
        // hours before boarding => % refunded
        'paliers' => [72 => 100, 48 => 80, 24 => 60, 0 => 50],
    ],

    // Public contact details (footer + contact page). Put the real ones in .env.
    'contact' => [
        'telephone' => env('SAFAR_TELEPHONE', '+212 5 00 00 00 00'),
        'email' => env('SAFAR_EMAIL', 'contact@blasti.ma'),
        'adresse' => env('SAFAR_ADRESSE', 'Casablanca, Maroc'),
    ],

    // Social networks shown in the footer; an empty link hides the icon.
    'social' => [
        'facebook' => env('SAFAR_FACEBOOK'),
        'instagram' => env('SAFAR_INSTAGRAM'),
        'tiktok' => env('SAFAR_TIKTOK'),
        'x' => env('SAFAR_X'),
        'linkedin' => env('SAFAR_LINKEDIN'),
    ],
];
