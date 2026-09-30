<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks the role permissions ("<service>.<action>", e.g. "voyages.update") on every admin route,
 * so hiding a menu entry is not the only protection. Super admins pass everywhere.
 */
class AdminPermission
{
    /** Route name prefix => permission service (same names as the role form). */
    private const SERVICES = [
        'admin.users.'           => 'utilisateurs',
        'admin.clients.'         => 'clients',
        'admin.roles.'           => 'roles',
        'admin.export.users'     => 'clients',
        'admin.export.'          => null, // resolved from the last segment below
        'villes.'                => 'villes',
        'type_voyages.'          => 'type voyages',
        'modeReglements.'        => 'mode reglements',
        'reservation.admin.'     => 'reservations',
        'voyages.'               => 'voyages',
        'societes.'              => 'societes',
        'autocars.'              => 'autocars',
        'equipements.'           => 'equipements',
        'autocarequipements.'    => 'equipements',
        'options.'               => 'options',
        'promotions.'            => 'promotions',
        'avis.'                  => 'avis',
        'admin.statistiques'     => 'statistiques',
        'autocaroptions.'        => 'options',
    ];

    /** Routes with their own right (special rights of App\Support\Droits); a list = any of them is enough. */
    private const ROUTES = [
        'reservation.admin.rembourser' => 'reservations.rembourser',
        'admin.alertes'                => 'finance.read',
        // counter sales (Guichet): selling = creating bookings
        'reservation.admin.guichet'         => 'reservations.create',
        'reservation.admin.guichet.vendre'  => 'reservations.create',
        'reservation.admin.guichet.vente'   => 'reservations.read',
        'reservation.admin.guichet.pdf'     => 'reservations.read',
        'reservation.admin.guichet.imprimer' => 'reservations.read',
        'reservation.admin.guichet.passager' => 'reservations.update',
        'reservation.admin.guichet.siege'    => 'reservations.update',
        'reservation.admin.guichet.retirer'  => 'reservations.update',
        'reservation.admin.guichet.mode'     => 'reservations.update',
        'reservation.admin.guichet.annuler'  => 'reservations.update',
        'admin.users.deconnecter'      => 'utilisateurs.update', // + super admin only (UserController::deconnecter)
        'admin.users.desactiver'       => 'utilisateurs.update', // + super admin only (UserController::desactiver)
        'reservation.admin.scanner'    => 'scanner.use',
        'reservation.admin.scan'       => 'scanner.use',
        'reservation.admin.embarquer'  => 'scanner.use',
        // cash is taken at the counter (bookings) or at the bus door (scanner)
        'reservation.admin.payer'      => ['reservations.update', 'scanner.use'],
        'voyages.passagers'            => ['voyages.read', 'scanner.use'],
        // staff can only create client accounts (UserController::store), so the Clients right is enough
        'admin.users.create'           => ['utilisateurs.create', 'clients.create'],
        'admin.users.store'            => ['utilisateurs.create', 'clients.create'],
    ];

    /** Last segment of the route name => permission action. */
    private const ACTIONS = [
        'index' => 'read', 'show' => 'read', 'list' => 'read',
        'create' => 'create', 'store' => 'create',
        'edit' => 'update', 'update' => 'update', 'update-password' => 'update',
        'destroy' => 'delete',
        'payer' => 'update', 'rembourser' => 'update', 'embarquer' => 'update',
        'passagers' => 'read', 'programmer' => 'create', 'publier' => 'update', 'scan' => 'read',
    ];

    /** Sections tried, in order, when a staff member without the dashboard permission logs in. */
    private const LANDING = [
        'reservations.read' => 'reservation.admin.index',
        'voyages.read'      => 'voyages.index',
        'autocars.read'     => 'autocars.index',
        'societes.read'     => 'societes.index',
        'clients.read'      => 'admin.clients.index',
        'scanner.use'       => 'reservation.admin.scanner',
        'villes.read'       => 'villes.index',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = (string) $request->route()?->getName();

        // company space: this account only sees its company's buses, trips, tickets and reviews
        \App\Support\SocieteScope::activer($user->societe_id);

        // Editing roles = granting permissions: reserved to the super admin to avoid self-promotion
        if (str_starts_with($routeName, 'admin.roles.') && ! $user->isSuperAdmin()) {
            abort(403, 'Seul le super administrateur peut gérer les rôles.');
        }

        // Logo color and name of the whole site
        if ((str_starts_with($routeName, 'admin.apparence.') || str_starts_with($routeName, 'admin.coordonnees.') || str_starts_with($routeName, 'admin.tarifs.')) && ! $user->isSuperAdmin()) {
            abort(403, "Seul le super administrateur peut modifier l'apparence.");
        }

        $cible = $request->route('user');
        if ($cible !== null && ! $cible instanceof \App\Models\User) {
            $cible = \App\Models\User::find($cible); // route parameter not bound yet
        }
        $permission = self::permissionFor($routeName, $cible);

        // A page with no permission mapped is reserved to the super admin (fail closed for new routes)
        $allowed = $permission === null
            ? $user->isSuperAdmin()
            : collect((array) $permission)->contains(fn ($p) => $user->hasPermission($p));
        if ($allowed) {
            return $next($request);
        }

        // Staff member without dashboard access: send them to the first section they may use
        if ($permission === 'dashboard.read') {
            foreach (self::LANDING as $landingPermission => $route) {
                if ($user->hasPermission($landingPermission)) {
                    return redirect()->route($route);
                }
            }
        }

        abort(403, "Vous n'avez pas la permission d'accéder à cette page.");
    }

    /** @return string|string[]|null  null = super admin only; a list = any of these rights */
    public static function permissionFor(string $routeName, mixed $cible = null): string|array|null
    {
        if ($routeName === 'admin') {
            return 'dashboard.read';
        }
        if (isset(self::ROUTES[$routeName])) {
            return self::ROUTES[$routeName];
        }
        // editing a client account needs the Clients right, a team account the Utilisateurs right
        if ($cible instanceof \App\Models\User && ! $cible->isadmin && str_starts_with($routeName, 'admin.users.')) {
            $segment = substr($routeName, strrpos($routeName, '.') + 1);

            return 'clients.' . (self::ACTIONS[$segment] ?? 'read');
        }

        foreach (self::SERVICES as $prefix => $service) {
            if (! str_starts_with($routeName, $prefix)) {
                continue;
            }

            // admin.export.reservations => reservations.read, admin.export.autocars => autocars.read ...
            if ($prefix === 'admin.export.') {
                return substr($routeName, strlen($prefix)) . '.read';
            }
            if (str_starts_with($prefix, 'admin.export.')) {
                return $service . '.read';
            }

            $segment = substr($routeName, strrpos($routeName, '.') + 1);
            $action = self::ACTIONS[$segment] ?? 'read';

            return $service . '.' . $action;
        }

        return null;
    }
}
