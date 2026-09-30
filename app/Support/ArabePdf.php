<?php

namespace App\Support;

/**
 * Arabic text for DomPDF, which neither joins Arabic letters nor writes right to left:
 *  1. each letter becomes its joined form (isolated / final / initial / medial, lam-alef ligatures),
 *  2. the text is put in visual order (Arabic runs reversed, numbers and Latin words kept as they are).
 * Used with a font that has these glyphs (DejaVu Sans, shipped with DomPDF). See BilletPdf.
 */
class ArabePdf
{
    /** letter => [isolated, final, initial, medial]; 2 forms = does not join the next letter */
    private const FORMES = [
        0x0621 => [0xFE80], 0x0622 => [0xFE81, 0xFE82], 0x0623 => [0xFE83, 0xFE84], 0x0624 => [0xFE85, 0xFE86],
        0x0625 => [0xFE87, 0xFE88], 0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C], 0x0627 => [0xFE8D, 0xFE8E],
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92], 0x0629 => [0xFE93, 0xFE94], 0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C], 0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4], 0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],
        0x062F => [0xFEA9, 0xFEAA], 0x0630 => [0xFEAB, 0xFEAC], 0x0631 => [0xFEAD, 0xFEAE], 0x0632 => [0xFEAF, 0xFEB0],
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4], 0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC], 0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4], 0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC], 0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4], 0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC], 0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4], 0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC], 0x0648 => [0xFEED, 0xFEEE], 0x0649 => [0xFEEF, 0xFEF0],
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
        // letters used in Moroccan names
        0x067E => [0xFB56, 0xFB57, 0xFB58, 0xFB59], 0x0686 => [0xFB7A, 0xFB7B, 0xFB7C, 0xFB7D],
        0x06A4 => [0xFB6A, 0xFB6B, 0xFB6C, 0xFB6D], 0x06AF => [0xFB92, 0xFB93, 0xFB94, 0xFB95],
        0x06A9 => [0xFB8E, 0xFB8F, 0xFB90, 0xFB91], 0x06CC => [0xFBFC, 0xFBFD, 0xFBFE, 0xFBFF],
    ];

    /** lam + alef => [isolated, final] */
    private const LAM_ALEF = [0x0622 => [0xFEF5, 0xFEF6], 0x0623 => [0xFEF7, 0xFEF8], 0x0625 => [0xFEF9, 0xFEFA], 0x0627 => [0xFEFB, 0xFEFC]];

    private const TATWEEL = 0x0640;

    private const MIROIR = ['(' => ')', ')' => '(', '[' => ']', ']' => '[', '{' => '}', '}' => '{', '«' => '»', '»' => '«', '<' => '>', '>' => '<'];

    public static function contientArabe(string $texte): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $texte);
    }

    /** Every text between tags of an HTML page, shaped and reordered when it holds Arabic. */
    public static function html(string $html): string
    {
        return preg_replace_callback('/>([^<]+)</u', function ($m) {
            // no Arabic, or already shaped (presentation forms): leave it
            if (! self::contientArabe($m[1]) || preg_match('/[\x{FB50}-\x{FEFF}]/u', $m[1])) {
                return $m[0];
            }
            // entities (&#039; ...) decoded first, or they would be turned around too
            $texte = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return '>' . htmlspecialchars(self::texte($texte), ENT_NOQUOTES, 'UTF-8') . '<';
        }, $html);
    }

    /**
     * A long sentence for a box about $max characters wide: cut into lines first (in reading order),
     * then each line turned around, so the lines stay in the right order. Returns safe HTML.
     */
    public static function lignes(string $texte, int $max): string
    {
        $lignes = [];
        $ligne = '';
        foreach (preg_split('/\s+/u', trim($texte)) as $mot) {
            if ($ligne !== '' && mb_strlen($ligne . ' ' . $mot) > $max) {
                $lignes[] = $ligne;
                $ligne = $mot;
            } else {
                $ligne = $ligne === '' ? $mot : $ligne . ' ' . $mot;
            }
        }
        if ($ligne !== '') {
            $lignes[] = $ligne;
        }

        return implode('<br>', array_map(fn ($l) => htmlspecialchars(self::texte($l), ENT_NOQUOTES, 'UTF-8'), $lignes));
    }

    /** One line of text: joined letters, right-to-left visual order. */
    public static function texte(string $texte): string
    {
        return self::ordreVisuel(self::joindre($texte));
    }

    private static function joindre(string $texte): string
    {
        $cps = array_map(fn ($c) => mb_ord($c, 'UTF-8'), mb_str_split($texte, 1, 'UTF-8'));
        $n = count($cps);
        $sortie = [];
        $transparent = fn ($cp) => ($cp >= 0x064B && $cp <= 0x065F) || $cp === 0x0670;
        $lettre = fn ($cp) => isset(self::FORMES[$cp]) || $cp === self::TATWEEL;
        $joint = fn ($cp) => $cp === self::TATWEEL || (isset(self::FORMES[$cp]) && count(self::FORMES[$cp]) === 4);

        // previous / next real letter (diacritics are skipped)
        $voisin = function (int $i, int $pas) use ($cps, $n, $transparent) {
            for ($j = $i + $pas; $j >= 0 && $j < $n; $j += $pas) {
                if (! $transparent($cps[$j])) {
                    return $cps[$j];
                }
            }

            return null;
        };

        for ($i = 0; $i < $n; $i++) {
            $cp = $cps[$i];
            if (! isset(self::FORMES[$cp])) {
                $sortie[] = $cp;
                continue;
            }
            $avant = $voisin($i, -1);
            $liéAvant = $avant !== null && $joint($avant);

            // lam + alef ligature
            if ($cp === 0x0644) {
                $j = $i + 1;
                while ($j < $n && $transparent($cps[$j])) {
                    $j++;
                }
                if ($j < $n && isset(self::LAM_ALEF[$cps[$j]])) {
                    $sortie[] = self::LAM_ALEF[$cps[$j]][$liéAvant ? 1 : 0];
                    for ($k = $i + 1; $k < $j; $k++) {
                        $sortie[] = $cps[$k];
                    }
                    $i = $j;
                    continue;
                }
            }

            $formes = self::FORMES[$cp];
            $apres = $voisin($i, 1);
            $liéApres = count($formes) === 4 && $apres !== null && $lettre($apres);
            $sortie[] = match (true) {
                $liéAvant && $liéApres => $formes[3],
                $liéAvant => $formes[1] ?? $formes[0],
                $liéApres => $formes[2],
                default => $formes[0],
            };
        }

        return implode('', array_map(fn ($cp) => mb_chr($cp, 'UTF-8'), $sortie));
    }

    /** Right-to-left line for an engine that only writes left to right. */
    private static function ordreVisuel(string $texte): string
    {
        // runs: Latin words / numbers stay left to right, together with the spaces and signs between them
        // ("N° 24", "+212 5 00 00 00 00", "Glover Group"); everything else is right to left
        $ltr = 'A-Za-z0-9\x{00C0}-\x{024F}';
        preg_match_all("/[+$ltr][$ltr .,:\\/\\-+%@_°']*[$ltr%]|[+$ltr]|[^+$ltr]+/u", $texte, $m);
        $morceaux = array_reverse($m[0]);

        return implode('', array_map(function ($morceau) use ($ltr) {
            if (preg_match("/^[+$ltr]/u", $morceau) && preg_match("/[$ltr]/u", $morceau)) {
                return $morceau;
            }
            $lettres = array_reverse(mb_str_split($morceau, 1, 'UTF-8'));

            return implode('', array_map(fn ($c) => self::MIROIR[$c] ?? $c, $lettres));
        }, $morceaux));
    }
}
