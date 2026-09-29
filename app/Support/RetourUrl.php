<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * "redirect_to" sent by the sign in / sign up popup: the page the client was on (e.g. the seat map).
 * Only a URL of this site is accepted, so the field cannot send people to another website.
 */
class RetourUrl
{
    public static function from(Request $request): ?string
    {
        $url = (string) $request->input('redirect_to', '');
        $base = rtrim(url('/'), '/');

        if ($url === '' || ! ($url === $base || str_starts_with($url, $base . '/') || str_starts_with($url, $base . '?'))) {
            return null;
        }

        return $url;
    }
}
