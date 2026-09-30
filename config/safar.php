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

    // Seats one client can book in a single order (one ticket per seat)
    'max_sieges' => (int) env('SAFAR_MAX_SIEGES', 6),

    /*
     * Unpaid tickets ("pay at boarding") must not block seats for nothing:
     *  - presence: the client is asked by e-mail to confirm he is coming `demande_heures` before boarding;
     *    without an answer `limite_heures` before boarding the ticket is cancelled and the seat goes back on sale.
     *    Tickets booked less than `demande_heures` before boarding count as confirmed (the client just booked).
     *  - max_non_payes: unpaid seats one account may hold on upcoming trips at the same time.
     *  - absences_max: after this many no-shows (unpaid ticket, bus gone, never scanned on a bus where the
     *    controller did scan), "pay at boarding" is no longer offered to the account (card only).
     */
    // Unpaid ticket (pay later): e-mail `demande_heures` after booking; cancelled `limite_heures` after the e-mail
    // without payment nor answer, or `paiement_heures` after the e-mail if the client confirmed but still did not pay.
    // A bus leaving before the deadline: the client pays the controller at the door (never cancelled after departure).
    'confirmation' => [
        'active' => (bool) env('SAFAR_CONFIRMATION_PRESENCE', true),
        'demande_heures' => (int) env('SAFAR_CONFIRMATION_DEMANDE_HEURES', 24),
        'limite_heures' => (int) env('SAFAR_CONFIRMATION_LIMITE_HEURES', 24),
        'paiement_heures' => (int) env('SAFAR_CONFIRMATION_PAIEMENT_HEURES', 36),
    ],
    'max_non_payes' => (int) env('SAFAR_MAX_NON_PAYES', 4),
    // orders booked without paying (at boarding or at the agency) per calendar month; then card only (0 = no limit)
    'non_payes_par_mois' => (int) env('SAFAR_NON_PAYES_PAR_MOIS', 2),
    'absences_max' => (int) env('SAFAR_ABSENCES_MAX', 2),

    // Return trip booked after an outbound order (same client, other way): % off the return (0 = no discount)
    'remise_retour_pourcent' => (float) env('SAFAR_REMISE_RETOUR', 10),

    // "Pay at an agency" mode: the order code must be paid within this many hours, else the tickets are cancelled
    'agence_delai_heures' => (int) env('SAFAR_AGENCE_DELAI_HEURES', 24),

    // Sales dashboard: platform commission on each ticket sold (% of the price)
    'commission_pourcent' => (float) env('SAFAR_COMMISSION', 10),

    // The client can move a ticket to another departure of the same trip until this many hours before boarding
    'modification_heures' => (int) env('SAFAR_MODIFICATION_HEURES', 48),

    // Local PC without CMI keys: test card payment page (no money moves; never used when APP_ENV is not "local")
    'paiement_test' => (bool) env('SAFAR_PAIEMENT_TEST', true),

    // Guest pages: seconds before the sign in / sign up popup opens (0 = never)
    'popup_connexion_secondes' => (int) env('SAFAR_POPUP_SECONDES', 40),

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

    // Legal notice (pages Mentions légales / CGV / Confidentialité): the company's official identifiers
    'legal' => [
        'societe' => env('SAFAR_SOCIETE'),               // raison sociale, e.g. "Blasti SARL"
        'forme' => env('SAFAR_FORME'),                   // SARL, SA...
        'capital' => env('SAFAR_CAPITAL'),               // "100 000 DH"
        'rc' => env('SAFAR_RC'),                         // registre du commerce
        'ice' => env('SAFAR_ICE'),
        'if' => env('SAFAR_IF'),                         // identifiant fiscal
        'directeur' => env('SAFAR_DIRECTEUR_PUBLICATION'),
        'hebergeur' => env('SAFAR_HEBERGEUR'),           // web host name + address
        'cndp' => env('SAFAR_CNDP'),                     // CNDP declaration / authorisation number (loi 09-08)
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
