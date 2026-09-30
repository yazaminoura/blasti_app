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
        'admin.clients.'         => 'utilisateurs',
        'admin.roles.'           => 'roles',
        'admin.export.users'     => 'utilisateurs',
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
        'utilisateurs.read' => 'admin.clients.index',
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
        if ((str_starts_with($routeName, 'admin.apparence.') || str_starts_with($routeName, 'admin.coordonnees.')) && ! $user->isSuperAdmin()) {
            abort(403, "Seul le super administrateur peut modifier l'apparence.");
        }

        $permission = self::permissionFor($routeName);

        // A page with no permission mapped is reserved to the super admin (fail closed for new routes)
        if ($permission === null ? $user->isSuperAdmin() : $user->hasPermission($permission)) {
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

    public static function permissionFor(string $routeName): ?string
    {
        if ($routeName === 'admin') {
            return 'dashboard.read';
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
