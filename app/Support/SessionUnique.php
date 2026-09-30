<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One open session per team / company account: the latest login wins, the older browser is logged out
 * at its next click (middleware UneSessionParCompte). Clients and the super admin are not limited.
 * Stops staff from sharing one login: every scan and every cash payment has one person behind it.
 */
class SessionUnique
{
    public const CLE = 'session_jeton';

    public static function limite(User $user): bool
    {
        return (bool) $user->isadmin && ! $user->isSuperAdmin();
    }

    /** Called right after a login with the form (not after a "remember me" cookie login). */
    public static function demarrer(User $user, Request $request): void
    {
        if (! $user->isadmin) {
            return;
        }
        $user->forceFill([
            'derniere_connexion_le' => now(),
            'derniere_connexion_ip' => $request->ip(),
            'derniere_connexion_appareil' => self::appareil((string) $request->userAgent()),
        ]);

        if (self::limite($user)) {
            $jeton = Str::random(64);
            $user->forceFill([self::CLE => $jeton]);
            $request->session()->put(self::CLE, $jeton);
            // the other browsers' sessions are closed now (the current one is saved again at the end of the request)
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $user->save();
    }

    /** Is this browser's session still the valid one? (A login that never went through the form has no token.) */
    public static function valide(User $user, Request $request): bool
    {
        return ! self::limite($user) || $request->session()->get(self::CLE) === $user->{self::CLE};
    }

    /** "Déconnecter partout": every browser of this account is logged out at its next click. */
    public static function deconnecterPartout(User $user): void
    {
        $user->forceFill([self::CLE => Str::random(64)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /** Is the account open somewhere right now (a session active within the session lifetime)? */
    public static function enLigne(User $user): bool
    {
        return DB::table('sessions')->where('user_id', $user->id)
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
            ->exists();
    }

    /** "Chrome · Windows" from the browser's user agent. */
    public static function appareil(string $ua): string
    {
        $navigateur = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Navigateur',
        };
        $systeme = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'appareil inconnu',
        };

        return $navigateur . ' · ' . $systeme;
    }
}
