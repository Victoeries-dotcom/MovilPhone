<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarrantyTicketLayoutTest extends TestCase
{
    public function test_ticket_adds_the_advance_and_final_payment_to_the_service_total(): void
    {
        // Protege la regla comercial: anticipo y faltante pagado forman el total cobrado del servicio.
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/ticket-entrega.blade.php');

        $this->assertStringContainsString('$total = (float) ($totalRegistrado ?? $ordenServicio->precioServicio());', $template);
        $this->assertStringContainsString('FALTANTE PAGADO:', $template);
        $this->assertStringContainsString('number_format($total, 2)', $template);
    }

    public function test_delivery_modal_shows_service_price_advance_and_remaining_balance(): void
    {
        // Verifica que el paso posterior al técnico explique cómo se obtiene el faltante antes de cobrarlo.
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/index.blade.php');

        $this->assertStringContainsString('Precio del servicio:', $template);
        $this->assertStringContainsString('Anticipo pagado:', $template);
        $this->assertStringContainsString('Falta por pagar:', $template);
        $this->assertStringContainsString('Math.max(0, entregaPrecioServicio - entregaAnticipo)', $template);
        $this->assertStringContainsString('faltanteEsperado.toFixed(2)', $template);
    }

    public function test_delivery_modal_blocks_progress_when_service_total_is_missing(): void
    {
        // El aviso visual y la validación JavaScript deben impedir llegar a la confirmación con total en cero.
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/index.blade.php');

        $this->assertStringContainsString('No colocaste el total del servicio.', $template);
        $this->assertStringContainsString("botonSiguiente.disabled = sinTotalServicio", $template);
        $this->assertStringContainsString('if (entregaPrecioServicio <= 0)', $template);
    }

    public function test_new_order_places_device_diagnostic_before_advance(): void
    {
        // Conserva el orden visual solicitado sin cambiar los nombres enviados al controlador.
        $template = file_get_contents(__DIR__.'/../../resources/views/ordenes/create.blade.php');
        $diagnosticPosition = strpos($template, 'Diagnóstico del dispositivo ($)');
        $advancePosition = strpos($template, 'Anticipo recibido ($)');

        $this->assertNotFalse($diagnosticPosition);
        $this->assertNotFalse($advancePosition);
        $this->assertLessThan($advancePosition, $diagnosticPosition);
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
