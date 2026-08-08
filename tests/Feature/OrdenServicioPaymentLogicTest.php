<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServicioPaymentLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_preserves_diagnostic_and_saves_exact_final_payment(): void
    {
        [$usuario, $tecnico, $sucursal, $orden] = $this->contextoDeEntrega();

        // Caso comercial solicitado: precio $500 menos anticipo $200 exige un pago final de $300.
        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre])
            ->post(route('ordenes.entregar', $orden), [
                'tecnico_entrega_id' => $tecnico->id,
                'cobro_final' => 300,
            ])
            ->assertRedirect(route('ordenes.ticketEntrega', $orden))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ordenes_servicio', [
            'id' => $orden->id,
            'estado' => 'ENTREGADO',
            'cobro_diagnostico' => 500,
            'presupuesto_total' => 500,
            'anticipo' => 200,
            'pago_final' => 300,
        ]);
        $this->assertDatabaseHas('movimientos_caja', [
            'os_id' => $orden->id,
            'monto' => 500,
            'anticipo' => 200,
            'saldo_pendiente' => 0,
        ]);
    }

    public function test_delivery_rejects_a_payment_that_does_not_match_the_balance(): void
    {
        [$usuario, $tecnico, $sucursal, $orden] = $this->contextoDeEntrega();

        // Impide liberar el equipo con $500 cuando el saldo real después del anticipo es $300.
        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre])
            ->post(route('ordenes.entregar', $orden), [
                'tecnico_entrega_id' => $tecnico->id,
                'cobro_final' => 500,
            ])
            ->assertSessionHasErrors([
                'cobro_final' => 'El pago final debe ser exactamente $300.00.',
            ]);

        $this->assertDatabaseHas('ordenes_servicio', [
            'id' => $orden->id,
            'estado' => 'TERMINADO',
            'cobro_diagnostico' => 500,
            'pago_final' => 0,
        ]);
    }

    /**
     * Construye una OS lista para entregar con relaciones reales de cliente, sucursal y técnico.
     *
     * @return array{User, User, Sucursal, OrdenServicio}
     */
    private function contextoDeEntrega(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL PAGOS']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);
        $tecnico = User::factory()->create(['rol' => 'tecnico', 'sucursal_id' => $sucursal->id]);
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE PAGO EXACTO',
            'telefono_principal' => '9911000099',
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        $orden = OrdenServicio::create([
            'numero_os' => 'PAG-2026-0001',
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'tecnico_id' => $tecnico->id,
            'estado' => 'TERMINADO',
            'tipo_dispositivo' => 'TELÉFONO',
            'marca' => 'SAMSUNG',
            'modelo' => 'GALAXY A15',
            'problema_reportado' => 'NO ENCIENDE',
            'accesorios_entregados' => 'CARGADOR',
            'estado_fisico' => 'ROTO',
            'cobro_diagnostico' => 500,
            'presupuesto_total' => 500,
            'anticipo' => 200,
            'pago_final' => 0,
        ]);

        return [$usuario, $tecnico, $sucursal, $orden];
    }
}
