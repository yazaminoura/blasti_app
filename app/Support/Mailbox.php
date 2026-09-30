<?php

namespace App\Support;

/**
 * "Open my mailbox" button after sign up: the webmail of the client's address (Gmail, Outlook...).
 * Unknown providers: null (no button, the client opens his mail app himself).
 */
class Mailbox
{
    private const PROVIDERS = [
        'Gmail' => ['https://mail.google.com/mail/u/0/#inbox', ['gmail.com', 'googlemail.com']],
        'Outlook' => ['https://outlook.live.com/mail/0/', ['outlook.com', 'outlook.fr', 'hotmail.com', 'hotmail.fr', 'live.com', 'live.fr', 'msn.com']],
        'Yahoo Mail' => ['https://mail.yahoo.com/', ['yahoo.com', 'yahoo.fr', 'ymail.com']],
        'iCloud Mail' => ['https://www.icloud.com/mail', ['icloud.com', 'me.com', 'mac.com']],
        'Proton Mail' => ['https://mail.proton.me/', ['proton.me', 'protonmail.com']],
        'GMX' => ['https://www.gmx.com/', ['gmx.com', 'gmx.fr']],
    ];

    /** ['url' => ..., 'nom' => 'Gmail'] or null. */
    public static function for(?string $email): ?array
    {
        $domain = strtolower((string) substr(strrchr((string) $email, '@') ?: '', 1));
        foreach (self::PROVIDERS as $nom => [$url, $domains]) {
            if (in_array($domain, $domains, true)) {
                return ['url' => $url, 'nom' => $nom];
            }
        }

        return null;
    }
}
