<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteReporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_superusuario_can_see_client_purchase_and_order_totals(): void
    {
        [$admin, $cliente, $sucursal, $usuario] = $this->scenario();

        Venta::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'sucursal_id' => $sucursal->id,
            'total' => 1250,
            'estado' => 'completada',
        ]);
        OrdenServicio::create([
            'numero_os' => 'REP-2026-0001',
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'tecnico_id' => $usuario->id,
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'problema_reportado' => 'Pantalla rota',
            'accesorios_entregados' => 'Ninguno',
            'estado_fisico' => 'Usado',
            'presupuesto_total' => 800,
        ]);

        // Este filtro comprueba las relaciones ventas.usuario_id y ordenes_servicio.tecnico_id.
        $response = $this->actingAs($admin)->get(route('clientes.reportes.usuarios', [
            'search' => $cliente->nombre,
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $usuario->id,
        ]));

        $response->assertOk()
            ->assertSee($cliente->nombre)
            ->assertSee('1,250.00')
            ->assertSee('800.00');
    }

    public function test_unapproved_role_cannot_open_or_export_the_report(): void
    {
        $vendedor = User::factory()->create(['rol' => 'vendedor']);

        foreach (['clientes.reportes.usuarios', 'clientes.reportes.usuarios.pdf', 'clientes.reportes.usuarios.excel'] as $route) {
            // Cada endpoint se protege para impedir que una URL escrita manualmente evada el menu.
            $this->actingAs($vendedor)->get(route($route))->assertForbidden();
        }
    }

    public function test_excel_export_is_available_for_usuario_role(): void
    {
        $usuario = User::factory()->create(['rol' => 'usuario']);

        $this->actingAs($usuario)
            ->get(route('clientes.reportes.usuarios.excel'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_pdf_export_generates_a_real_pdf_file(): void
    {
        $admin = User::factory()->create(['rol' => 'superusuario']);

        $response = $this->actingAs($admin)->get(route('clientes.reportes.usuarios.pdf'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * Prepara los registros minimos que representan un cliente atendido en una sucursal.
     */
    private function scenario(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'Sucursal Reportes']);
        $admin = User::factory()->create(['rol' => 'superusuario']);
        $usuario = User::factory()->create([
            'name' => 'Usuario que atendio',
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
        ]);
        $cliente = Cliente::create([
            'nombre' => 'Cliente del reporte',
            'telefono_principal' => '5550001122',
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        return [$admin, $cliente, $sucursal, $usuario];
    }
}
