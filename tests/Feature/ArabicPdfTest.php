<?php

namespace Tests\Feature;

use App\Support\ArabePdf;
use Tests\TestCase;

/** Arabic in the PDF ticket: letters joined, right-to-left order, numbers and Latin words untouched. */
class ArabicPdfTest extends TestCase
{
    public function test_arabic_letters_are_joined_and_written_right_to_left(): void
    {
        // مكناس: meem (initial) kaf (medial) noon (medial) alef (final) seen (isolated), then turned around
        $this->assertSame("\u{FEB1}\u{FE8E}\u{FEE8}\u{FEDC}\u{FEE3}", ArabePdf::texte('مكناس'));
        // lam + alef ligature (سلام: seen initial, lam-alef final, meem isolated -> reversed)
        $this->assertSame("\u{FEE1}\u{FEFC}\u{FEB3}", ArabePdf::texte('سلام'));
        // numbers and Latin keep their own direction inside an Arabic line
        $this->assertStringContainsString('N° 24', ArabePdf::texte('مقعد N° 24'));
        $this->assertStringContainsString('03/10/2026', ArabePdf::texte('سبت 03/10/2026'));
    }

    public function test_an_html_page_is_shaped_but_entities_and_latin_text_stay_right(): void
    {
        $html = ArabePdf::html('<p>Bonjour</p><p>ل&#039;ا</p>');
        $this->assertStringContainsString('<p>Bonjour</p>', $html);
        $this->assertStringNotContainsString('&#039;', $html);

        // long sentences: cut into lines first, lines kept in reading order
        $lignes = explode('<br>', ArabePdf::lignes('كلمة كلمة كلمة كلمة', 10));
        $this->assertCount(2, $lignes);
    }
}
