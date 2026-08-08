<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarrantyTicketLayoutTest extends TestCase
{
    public function test_ticket_adds_the_advance_and_final_payment_to_the_service_total(): void
    {
        // Protege la regla comercial: anticipo y faltante pagado forman el total cobrado del servicio.
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/ticket-entrega.blade.php');

        $this->assertStringContainsString('$total = (float) ($totalRegistrado ?? ($anticipo + $faltante));', $template);
        $this->assertStringContainsString('FALTANTE PAGADO:', $template);
        $this->assertStringContainsString('number_format($total, 2)', $template);
    }

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
