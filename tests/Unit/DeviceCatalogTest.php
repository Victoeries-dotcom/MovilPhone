<?php

namespace Tests\Unit;

use Tests\TestCase;

class DeviceCatalogTest extends TestCase
{
    /**
     * Verifica que el catálogo conectado al formulario de Órdenes de Servicio
     * conserve la relación dispositivo -> marca -> modelo para Apple.
     */
    public function test_apple_phone_models_are_available_in_the_device_catalog(): void
    {
        $catalog = config('device_catalog');

        $this->assertArrayHasKey('Teléfono celular', $catalog);
        $this->assertArrayHasKey('Apple', $catalog['Teléfono celular']);
        $this->assertContains('iPhone 16', $catalog['Teléfono celular']['Apple']);
    }

    /**
     * Comprueba que el catálogo cubra varias familias reparables y no dependa
     * únicamente de teléfonos celulares.
     */
    public function test_catalog_contains_multiple_repairable_device_types(): void
    {
        $catalog = config('device_catalog');

        $this->assertArrayHasKey('Tableta', $catalog);
        $this->assertArrayHasKey('Laptop', $catalog);
        $this->assertArrayHasKey('Consola de videojuegos', $catalog);
        $this->assertArrayHasKey('Televisión / Smart TV', $catalog);
        $this->assertGreaterThanOrEqual(12, count($catalog));
    }
}
