<?php

namespace App\Http\Middleware;

use App\Support\SessionUnique;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** A team / company account opened in another browser: this older browser is logged out (App\Support\SessionUnique). */
class UneSessionParCompte
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || SessionUnique::valide($user, $request)) {
            return $next($request);
        }

        // only this browser (the remember token stays valid for the new one)
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'Votre compte a été ouvert sur un autre appareil : vous avez été déconnecté ici. Un compte de l\'équipe ne peut être ouvert qu\'à un seul endroit à la fois.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
