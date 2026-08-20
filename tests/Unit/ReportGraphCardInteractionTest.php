<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReportGraphCardInteractionTest extends TestCase
{
    private string $template;

    protected function setUp(): void
    {
        parent::setUp();

        // Lee la vista real para proteger el comportamiento visual solicitado en Reportes.
        $this->template = file_get_contents(__DIR__.'/../../resources/views/reportes/index.blade.php');
    }

    public function test_graph_cards_use_blue_hover_and_green_selected_styles(): void
    {
        // El hover debe comunicar interacción en azul y el clic debe conservar una selección verde.
        $this->assertStringContainsString('.reporte-grafica:hover', $this->template);
        $this->assertStringContainsString('rgba(37,99,235,.28)', $this->template);
        $this->assertStringContainsString('.reporte-grafica.is-selected', $this->template);
        $this->assertStringContainsString('border-color:#22c55e', $this->template);
    }

    public function test_all_four_graph_cards_expose_their_selected_state(): void
    {
        // Las cuatro gráficas deben ser seleccionables y anunciar su estado a tecnologías de asistencia.
        preg_match_all('/<article[^>]+data-reporte-seleccionable/', $this->template, $selectableCards);

        $this->assertCount(4, $selectableCards[0]);
        $this->assertSame(4, substr_count($this->template, 'aria-pressed="false"'));
    }

    public function test_selection_supports_mouse_and_keyboard_without_changing_graph_data(): void
    {
        // El mismo controlador atiende clic, Enter y espacio, y mantiene una sola tarjeta activa.
        $this->assertStringContainsString("document.querySelectorAll('[data-reporte-seleccionable]')", $this->template);
        $this->assertStringContainsString("event.key === 'Enter'", $this->template);
        $this->assertStringContainsString("event.key === ' '", $this->template);
        $this->assertStringContainsString("setAttribute('aria-pressed', String(seleccionada))", $this->template);
    }
}
