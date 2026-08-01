<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarrantyTicketLayoutTest extends TestCase
{
    public function test_warranty_policy_is_rendered_before_the_internal_ticket_number(): void
    {
        // Revisa el orden real del recibo: política al final del contenido y folio inmediatamente después.
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/ticket-entrega.blade.php');
        $warrantyPosition = strpos($template, '<section class="ticket-warranty">');
        $folioPosition = strpos($template, '<div>Folio: #');

        $this->assertNotFalse($warrantyPosition);
        $this->assertNotFalse($folioPosition);
        $this->assertLessThan($folioPosition, $warrantyPosition);
    }
}
