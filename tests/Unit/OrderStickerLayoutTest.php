<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OrderStickerLayoutTest extends TestCase
{
    public function test_local_contact_appears_immediately_below_the_dynamic_order_number(): void
    {
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/sticker.blade.php');

        $folioPosition = strpos($template, '{{ $ordenServicio->numero_os }}');
        $contactPosition = strpos($template, 'CONTÁCTANOS: 9911098036');
        $headerEndPosition = strpos($template, '</div>', $contactPosition);

        // El folio sigue siendo dinámico y el teléfono queda dentro del mismo encabezado, justo debajo.
        $this->assertNotFalse($folioPosition);
        $this->assertNotFalse($contactPosition);
        $this->assertNotFalse($headerEndPosition);
        $this->assertLessThan($contactPosition, $folioPosition);
        $this->assertLessThan($headerEndPosition, $contactPosition);
    }

    public function test_sticker_uses_larger_readable_text_without_clipping_long_values(): void
    {
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/sticker.blade.php');

        // Protege los tamaños solicitados y el ajuste de palabras dentro del ancho físico de 300 px.
        $this->assertStringContainsString('font-size:22px', $template);
        $this->assertStringContainsString('font-size:19px', $template);
        $this->assertStringContainsString('font-size:14px', $template);
        $this->assertStringContainsString('font-size: 15px', $template);
        $this->assertStringContainsString('overflow-wrap: anywhere', $template);
    }
}
