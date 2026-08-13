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
}
