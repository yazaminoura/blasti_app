<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Name and color chosen in Admin > Paramètres > Apparence
        \App\Models\Parametre::appliquerALaConfig();

        Gate::before(function ($user, $ability) {
            return $user->hasPermission($ability) ? true : null;
        });











        //////////////////////////////////////////
        if (str_contains(request()->url(), 'ngrok-free.dev')) {
        URL::forceScheme('https');
    }
    }
}
