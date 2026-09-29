<?php

namespace App\Support;

use App\Models\Reservation;

/**
 * CMI (Centre Monétique Interbancaire) "3D Pay Hosting" card payment.
 *
 * Flow: the client is sent to the CMI page with a signed form (fields()), pays there, then CMI
 *  - calls our callback server-to-server (verify() + answer "ACTION=POSTAUTH" to accept),
 *  - and redirects the client's browser to okUrl / failUrl.
 * The reservation is only confirmed by the callback, never by the browser redirect.
 * Disabled until CMI_CLIENT_ID and CMI_STORE_KEY are set (.env).
 */
class Cmi
{
    public static function enabled(): bool
    {
        return filled(config('services.cmi.client_id')) && filled(config('services.cmi.store_key'));
    }

    /**
     * No CMI keys yet and the app runs on a developer PC (APP_ENV=local): a clearly labelled test payment page
     * replaces the CMI page, so the whole flow can be tried. No money moves. Never active in production.
     */
    public static function testMode(): bool
    {
        return ! self::enabled() && app()->environment('local') && (bool) config('safar.paiement_test', true);
    }

    /** Card payment can be offered to clients (real CMI, or the local test page). */
    public static function available(): bool
    {
        return self::enabled() || self::testMode();
    }

    public static function gatewayUrl(): string
    {
        return (string) config('services.cmi.gateway_url');
    }

    /** Amount of the whole order (its seats are booked, paid and expire together), as sent to CMI ("123.00"). */
    public static function amount(Reservation $reservation): string
    {
        return number_format($reservation->commandeBillets()->sum(fn ($b) => $b->total()), 2, '.', '');
    }

    /** Signed form fields posted to the CMI gateway. */
    public static function fields(Reservation $reservation): array
    {
        $user = $reservation->user;
        $fields = [
            'clientid' => (string) config('services.cmi.client_id'),
            // the whole order: every seat booked together is paid in one go
            'amount' => self::amount($reservation),
            'currency' => '504', // MAD
            'oid' => self::orderId($reservation),
            'okUrl' => route('payment.cmi.ok', $reservation),
            'failUrl' => route('payment.cmi.fail', $reservation),
            'callbackUrl' => route('payment.cmi.callback'),
            'shopurl' => route('client.reservations.show', $reservation->voyage_id),
            'TranType' => 'PreAuth',
            'storetype' => '3D_PAY_HOSTING',
            'hashAlgorithm' => 'ver3',
            'lang' => app()->getLocale() === 'ar' ? 'ar' : (app()->getLocale() === 'en' ? 'en' : 'fr'),
            'rnd' => (string) microtime(true),
            'encoding' => 'UTF-8',
            'BillToName' => (string) $user?->name,
            'email' => (string) $user?->email,
            'tel' => (string) $user?->telephone,
            'refreshtime' => '5',
        ];
        $fields['HASH'] = self::hash($fields);

        return $fields;
    }

    /** Checks the signature of the data posted back by CMI. */
    public static function verify(array $posted): bool
    {
        $received = $posted['HASH'] ?? $posted['hash'] ?? null;

        return is_string($received) && hash_equals(self::hash($posted), $received);
    }

    /** Order id sent to CMI: the reservation id plus a suffix so a retried payment gets a new id. */
    public static function orderId(Reservation $reservation): string
    {
        return 'BL' . $reservation->id . '-' . $reservation->created_at?->format('His');
    }

    /** CMI "ver3" hash: values sorted by field name (case-insensitive), escaped, joined with "|", + store key, SHA-512, base64. */
    public static function hash(array $params): string
    {
        $keys = array_keys($params);
        natcasesort($keys);

        $plain = '';
        foreach ($keys as $key) {
            $lower = strtolower($key);
            if ($lower === 'hash' || $lower === 'encoding') {
                continue;
            }
            $value = trim(html_entity_decode(preg_replace("/\n$/", '', (string) $params[$key]), ENT_QUOTES, 'UTF-8'));
            $plain .= self::escape($value) . '|';
        }
        $plain .= self::escape((string) config('services.cmi.store_key'));

        return base64_encode(pack('H*', hash('sha512', $plain)));
    }

    private static function escape(string $value): string
    {
        return str_replace('|', '\\|', str_replace('\\', '\\\\', $value));
    }
}
