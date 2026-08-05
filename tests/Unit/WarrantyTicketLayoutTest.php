<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarrantyTicketLayoutTest extends TestCase
{
    public function test_ticket_uses_repair_as_total_and_subtracts_the_advance_from_the_balance(): void
    {
        // Protege la regla comercial del recibo: el anticipo reduce el saldo, pero no aumenta el total.
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/ticket-entrega.blade.php');

        $this->assertStringContainsString('$total = (float) ($totalRegistrado ?? $reparacion);', $template);
        $this->assertStringContainsString('$faltante = max(0, $total - $anticipo);', $template);
        $this->assertStringContainsString('FALTANTE DEL PAGO:', $template);
        $this->assertStringNotContainsString('$anticipo + $reparacion', $template);
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
