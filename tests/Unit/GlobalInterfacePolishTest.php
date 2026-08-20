<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GlobalInterfacePolishTest extends TestCase
{
    private string $stylesheet;

    protected function setUp(): void
    {
        parent::setUp();

        // Lee la hoja global real para proteger el acabado profesional solicitado en todo el sistema.
        $this->stylesheet = file_get_contents(__DIR__.'/../../public/css/movilphone-ui.css');
    }

    public function test_light_theme_uses_a_softer_canvas_and_clear_card_borders(): void
    {
        // El lienzo debe ser más claro que antes y las tarjetas deben conservar un límite gris visible.
        $this->assertStringContainsString('--ui-canvas:#f6f8fb;', $this->stylesheet);
        $this->assertStringContainsString('--ui-border:#dfe5ec;', $this->stylesheet);
        $this->assertStringContainsString('--ui-card-hover-border:#93b4e8;', $this->stylesheet);
    }

    public function test_cards_use_reduced_depth_and_a_discreet_blue_hover(): void
    {
        // Las variables compartidas reducen la sombra normal y añaden respuesta azul sin exagerarla.
        $this->assertStringContainsString('--ui-shadow-sm:0 2px 8px rgba(15,23,42,.045);', $this->stylesheet);
        $this->assertStringContainsString('--ui-card-hover-shadow:0 8px 20px rgba(37,99,235,.10);', $this->stylesheet);
        $this->assertStringContainsString('box-shadow:var(--ui-card-hover-shadow) !important;', $this->stylesheet);
    }

    public function test_secondary_text_has_stronger_contrast_without_becoming_primary_text(): void
    {
        // El gris secundario se oscurece de forma moderada y continúa separado del texto principal.
        $this->assertStringContainsString('--ui-muted:#526176;', $this->stylesheet);
        $this->assertStringNotContainsString('--ui-muted:#17213d;', $this->stylesheet);
    }
}
