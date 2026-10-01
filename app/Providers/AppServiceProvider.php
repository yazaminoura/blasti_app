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

        // fresh clone on a developer PC: demo data once every migration is done (DEMO_DATA, never in production)
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\MigrationsEnded::class, function ($event) {
            if ($event->method === 'up') {
                \Database\Seeders\DemoDataSeeder::chargerSiVide();
            }
        });

        // Name and color chosen in Admin > Paramètres > Apparence
        \App\Models\Parametre::appliquerALaConfig();

        Gate::before(function ($user, $ability) {
            return $user->hasPermission($ability) ? true : null;
        });

        // Sign up email in the client's language, with the brand name (Laravel's default is English)
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(fn ($user, string $url) => (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject(__('Confirmez votre adresse e-mail | :brand', ['brand' => config('safar.nom')]))
            ->greeting(__('Bonjour :name,', ['name' => $user->name]))
            ->line(__('Bienvenue sur :brand ! Cliquez sur le bouton ci-dessous pour confirmer votre adresse e-mail. Vos billets seront envoyés à cette adresse.', ['brand' => config('safar.nom')]))
            ->action(__('Confirmer mon adresse e-mail'), $url)
            ->line(__('Ce lien est valable 60 minutes. Si vous n\'avez pas créé de compte, ignorez cet e-mail.'))
            ->salutation(__('L\'équipe :brand', ['brand' => config('safar.nom')])));











        //////////////////////////////////////////
        if (str_contains(request()->url(), 'ngrok-free.dev')) {
        URL::forceScheme('https');
    }
    }
}
