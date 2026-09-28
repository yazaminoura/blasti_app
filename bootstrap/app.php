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
            \App\Http\Middleware\SetLocale::class,
        ]);

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
