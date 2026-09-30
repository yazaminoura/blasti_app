<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Company space (App\Support\SocieteScope): switched on for back-office pages of an account tied to a company,
 * off everywhere else (public site). Runs before SubstituteBindings (bootstrap/app.php priority list), so a record
 * of another company opened by its URL is already a 404.
 */
class ResetSocieteScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminPage = in_array('admin.permission', (array) $request->route()?->gatherMiddleware(), true);
        \App\Support\SocieteScope::activer($adminPage ? $request->user()?->societe_id : null);

        return $next($request);
    }

    /** and ends unfiltered (long-running workers, tests) */
    public function terminate(Request $request, Response $response): void
    {
        \App\Support\SocieteScope::activer(null);
    }
}
