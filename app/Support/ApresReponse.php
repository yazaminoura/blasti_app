<?php

namespace App\Support;

/**
 * Slow work (e-mail + PDF, SMTP) that nobody should wait for: during a web request it runs once the response
 * is sent to the browser (or to the CMI server); in commands, the scheduler and tests it runs at once.
 */
class ApresReponse
{
    public static function executer(callable $travail, ?string $nom = null): void
    {
        if (app()->runningInConsole()) {
            $travail();

            return;
        }
        \Illuminate\Support\defer($travail, $nom, always: true);
    }
}
