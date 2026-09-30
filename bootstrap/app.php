<?php

use App\Http\Middleware\AdminIsValid;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\ResetSocieteScope::class,
            \App\Http\Middleware\SetLocale::class,
            // one open session per team / company account (the latest login wins)
            \App\Http\Middleware\UneSessionParCompte::class,
        ]);

        // company filter before the route records are loaded (another company's record = 404)
        $middleware->prependToPriorityList(\Illuminate\Routing\Middleware\SubstituteBindings::class, \App\Http\Middleware\ResetSocieteScope::class);

        $middleware->alias([
            'admin' => AdminIsValid::class,
            'admin.permission' => \App\Http\Middleware\AdminPermission::class,
        ]);

        // CMI posts back from its own site (payment result pages + server callback): no CSRF token there;
        // those routes check the CMI signature instead
        $middleware->validateCsrfTokens(except: ['paiement/*']);

        // Trust all proxies (needed for ngrok / reverse proxies)
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
