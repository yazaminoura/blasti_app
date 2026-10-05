<?php

namespace App\Support;

/**
 * Logo images in the color chosen in Admin > Paramètres > Apparence.
 *
 * The drawings are blue with an orange accent. Each image is copied once per color with:
 *  - its blue parts moved to the chosen color,
 *  - its orange parts turned white (always): pure white on the versions shown on dark backgrounds,
 *    a very light grey-white on the others so the shape of the bus stays visible on a white page.
 * Copies live in public/assets/img/brand/<hex>-w/. Views call BrandImages::url('blasti-logo.png').
 */
class BrandImages
{
    public const DEFAULT_COLOR = '#0B4FC4';

    public const FILES = [
        'blasti-logo.png', 'blasti-logo-dark.png', 'blasti-icon.png',
        'blasti-bus.png', 'blasti-hero-bus.png', 'blasti-hero-bus-bg.png',
    ];

    /** Versions displayed on a dark background: their orange becomes pure white. */
    private const ON_DARK = ['blasti-logo-dark.png', 'blasti-hero-bus.png'];

    public static function hasCustomLogo(): bool
    {
        return filled(config('safar.logo'));
    }

    public static function hasCustomLogoDark(): bool
    {
        return filled(config('safar.logo_dark'));
    }

    public static function isDefaultBrand(): bool
    {
        return strtolower(trim((string) config('safar.nom', 'Blasti'))) === 'blasti';
    }

    public static function logoUrl(): string
    {
        if (self::hasCustomLogo()) {
            return asset('storage/' . config('safar.logo'));
        }

        return self::url('blasti-logo.png');
    }

    public static function logoDarkUrl(): string
    {
        if (self::hasCustomLogoDark()) {
            return asset('storage/' . config('safar.logo_dark'));
        }
        if (self::hasCustomLogo()) {
            return asset('storage/' . config('safar.logo'));
        }

        return self::url('blasti-logo-dark.png');
    }

    public static function url(string $file): string
    {
        if ($file === 'blasti-logo.png' && self::hasCustomLogo()) {
            return asset('storage/' . config('safar.logo'));
        }
        if ($file === 'blasti-logo-dark.png') {
            if (self::hasCustomLogoDark()) {
                return asset('storage/' . config('safar.logo_dark'));
            }
            if (self::hasCustomLogo()) {
                return asset('storage/' . config('safar.logo'));
            }
        }

        return asset(self::relative($file));
    }

    /** Absolute path (the PDF ticket embeds the file). */
    public static function path(string $file): string
    {
        if ($file === 'blasti-logo.png' && self::hasCustomLogo()) {
            $p = storage_path('app/public/' . config('safar.logo'));
            if (is_file($p)) {
                return $p;
            }
        }
        if ($file === 'blasti-logo-dark.png') {
            if (self::hasCustomLogoDark()) {
                $p = storage_path('app/public/' . config('safar.logo_dark'));
                if (is_file($p)) {
                    return $p;
                }
            }
            if (self::hasCustomLogo()) {
                $p = storage_path('app/public/' . config('safar.logo'));
                if (is_file($p)) {
                    return $p;
                }
            }
        }

        return public_path(self::relative($file));
    }

    /** Makes every copy for a color (called when the color is saved). Skipped in tests (a few seconds of image work). */
    public static function generate(string $color): void
    {
        if (app()->runningUnitTests()) {
            return;
        }
        foreach (self::FILES as $file) {
            self::recolored($file, $color);
        }
        self::favicon();
    }

    /**
     * public/favicon.ico = the current bus icon (asked by the browser for pages without an icon, e.g. a PDF in a tab).
     * An .ico may hold a PNG as it is: 6-byte header + 16-byte entry + the PNG.
     */
    public static function favicon(): void
    {
        $png = @file_get_contents(self::path('blasti-icon.png'));
        $taille = $png ? @getimagesizefromstring($png) : false;
        if (! $png || ! $taille) {
            return;
        }
        $cote = fn (int $px) => $px >= 256 ? 0 : $px; // 0 means 256 in an .ico
        $ico = pack('vvv', 0, 1, 1)
            . pack('CCCCvvVV', $cote($taille[0]), $cote($taille[1]), 0, 0, 1, 32, strlen($png), 22)
            . $png;
        @file_put_contents(public_path('favicon.ico'), $ico);
    }

    private static function relative(string $file): string
    {
        if (! in_array($file, self::FILES, true)) {
            return 'assets/img/' . $file;
        }
        $color = strtoupper((string) config('safar.couleur', self::DEFAULT_COLOR));

        return self::recolored($file, $color) ?? 'assets/img/' . $file;
    }

    private static function recolored(string $file, string $color): ?string
    {
        $relative = 'assets/img/brand/' . ltrim(strtoupper($color), '#') . '-w/' . $file;
        $target = public_path($relative);
        if (is_file($target)) {
            return $relative;
        }

        $source = public_path('assets/img/' . $file);
        if (! is_file($source) || ! function_exists('imagecreatefrompng')) {
            return null;
        }

        try {
            @mkdir(dirname($target), 0775, true);
            self::recolor($source, $target, $color, in_array($file, self::ON_DARK, true));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        return $relative;
    }

    private static function recolor(string $source, string $target, string $color, bool $onDark): void
    {
        [$th, $ts, $tl] = self::hsl(...sscanf($color, '#%02x%02x%02x'));
        [, $bs, $bl] = self::hsl(...sscanf(self::DEFAULT_COLOR, '#%02x%02x%02x'));
        $satFactor = $bs > 0 ? $ts / $bs : 1;
        $lightShift = ($tl - $bl) * 0.6;
        // white range for the orange parts (keeps a bit of the shading so the shapes stay readable)
        [$whiteMin, $whiteRange] = $onDark ? [235, 20] : [208, 30];

        $im = imagecreatefrompng($source);
        imagepalettetotruecolor($im);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $w = imagesx($im);
        $h = imagesy($im);
        $cache = [];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($im, $x, $y);
                $alpha = ($c >> 24) & 127;
                if ($alpha === 127) {
                    continue;
                }
                $rgb = $c & 0xFFFFFF;
                if (! isset($cache[$rgb])) {
                    [$hue, $s, $l] = self::hsl(($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255);
                    if ($s > 0.12 && $hue >= 185 && $hue <= 265) {
                        // blue parts -> chosen color
                        $cache[$rgb] = self::rgb($th, min(1, $s * $satFactor), max(0, min(1, $l + $lightShift)));
                    } elseif ($s > 0.35 && $hue >= 5 && $hue <= 50) {
                        // orange parts -> white
                        $v = (int) round($whiteMin + $whiteRange * min(1, $l * 1.3));
                        $cache[$rgb] = [$v, $v, $v];
                    } else {
                        $cache[$rgb] = null;
                    }
                }
                if ($cache[$rgb] !== null) {
                    [$r, $g, $b] = $cache[$rgb];
                    imagesetpixel($im, $x, $y, imagecolorallocatealpha($im, $r, $g, $b, $alpha));
                }
            }
        }

        imagepng($im, $target, 9);
    }

    private static function hsl(int $r, int $g, int $b): array
    {
        $r /= 255; $g /= 255; $b /= 255;
        $max = max($r, $g, $b); $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max === $min) {
            return [0, 0, $l];
        }
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        $h = match ($max) {
            $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [$h * 60, $s, $l];
    }

    private static function rgb(float $h, float $s, float $l): array
    {
        if ($s == 0) {
            $v = (int) round($l * 255);

            return [$v, $v, $v];
        }
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $h /= 360;
        $f = function ($t) use ($p, $q) {
            if ($t < 0) $t += 1;
            if ($t > 1) $t -= 1;
            if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
            if ($t < 1 / 2) return $q;
            if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;

            return $p;
        };

        return [(int) round($f($h + 1 / 3) * 255), (int) round($f($h) * 255), (int) round($f($h - 1 / 3) * 255)];
    }
}
