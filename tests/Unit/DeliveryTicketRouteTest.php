<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeliveryTicketRouteTest extends TestCase
{
    public function test_delivery_form_uses_laravel_route_instead_of_a_root_relative_url(): void
    {
        // La vista debe respetar APP_URL o cualquier carpeta base para que el POST llegue a entregar().
        $template = file_get_contents(resource_path('views/ordenes/index.blade.php'));

        $this->assertStringContainsString("route('ordenes.entregar'", $template);
        $this->assertStringNotContainsString("form.action = '/ordenes/' + entregaOrdenId", $template);
    }
}
