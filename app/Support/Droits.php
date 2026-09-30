<?php

namespace App\Support;

/**
 * Every back-office right and the ready-made roles.
 * A right is "<service in lowercase>.<action>" (grid of the role form) or one of the special rights below.
 */
class Droits
{
    /** Rows of the role grid. */
    public const SERVICES = ['Dashboard', 'Clients', 'Utilisateurs', 'Roles', 'Villes', 'Type Voyages', 'Mode Reglements', 'Reservations', 'Voyages', 'Societes', 'Autocars', 'Equipements', 'Options', 'Promotions', 'Avis', 'Statistiques'];

    /** Columns of the role grid. */
    public const ACTIONS = ['read', 'create', 'update', 'delete'];

    /** Rights that are not a whole section: name => [label, explanation]. */
    public const SPECIAUX = [
        'reservations.rembourser' => ['Rembourser un billet', 'Marquer un billet annulé comme remboursé.'],
        'finance.read' => ["Voir le chiffre d'affaires", "Les montants totaux du tableau de bord et de la liste des réservations."],
        'scanner.use' => ['Scanner et faire monter', "Scanner les billets, encaisser à la porte du bus, liste des passagers."],
    ];

    /** Every valid right name. */
    public static function toutes(): array
    {
        $names = [];
        foreach (self::SERVICES as $service) {
            foreach (self::ACTIONS as $action) {
                $names[] = strtolower($service) . '.' . $action;
            }
        }

        return array_merge($names, array_keys(self::SPECIAUX));
    }

    /**
     * Ready-made roles (slug => [name, rights]). "service.*" = the 4 actions of the section.
     * Company roles only make sense on an account linked to a company (users.societe_id).
     */
    public static function roles(): array
    {
        $crud = fn (string ...$services) => collect($services)
            ->flatMap(fn ($s) => array_map(fn ($a) => "$s.$a", self::ACTIONS))->all();
        $gestion = ['dashboard', 'clients', 'villes', 'type voyages', 'mode reglements', 'reservations', 'voyages',
            'societes', 'autocars', 'equipements', 'options', 'promotions', 'avis', 'statistiques'];

        return [
            'directeur' => ['Directeur', array_merge($crud(...$gestion), array_keys(self::SPECIAUX))],
            'guichetier' => ['Guichetier', ['dashboard.read', 'reservations.read', 'reservations.create', 'reservations.update', 'voyages.read', 'clients.read', 'clients.create']],
            'controleur' => ['Contrôleur', ['scanner.use']],
            'service-client' => ['Service client', ['dashboard.read', 'reservations.read', 'reservations.update', 'reservations.delete', 'reservations.rembourser',
                'voyages.read', 'clients.read', 'clients.update', 'avis.read', 'avis.update']],
            'planning' => ['Planning', array_merge(['dashboard.read'], $crud('voyages', 'autocars', 'villes', 'type voyages', 'equipements', 'options'), ['societes.read'])],
            'comptable' => ['Comptable', ['dashboard.read', 'finance.read', 'statistiques.read', 'reservations.read', 'voyages.read', 'clients.read', 'societes.read']],
            'marketing' => ['Marketing', array_merge(['dashboard.read', 'villes.read', 'villes.update'], $crud('promotions'), ['avis.read', 'avis.update', 'avis.delete'])],
            'compagnie' => ['Compagnie – Responsable', ['dashboard.read', 'finance.read', 'reservations.read', 'reservations.update', 'scanner.use',
                'voyages.read', 'voyages.create', 'voyages.update', 'voyages.delete', 'autocars.read', 'autocars.create', 'autocars.update', 'avis.read']],
            'compagnie-controleur' => ['Compagnie – Contrôleur', ['scanner.use']],
        ];
    }
}
