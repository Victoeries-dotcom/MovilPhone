<?php

namespace Tests\Feature;

use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardIncomePeriodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que el recuadro separe día, semana y mes sin sumar egresos u otras sucursales.
     */
    public function test_panel_calcula_ingresos_de_dia_semana_y_mes_para_la_sucursal_activa(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 15:00:00');
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $otraSucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);

        $this->movimiento($sucursal->id, 'INGRESO', 100, '2026-08-12 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 200, '2026-08-10 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 300, '2026-08-02 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 400, '2026-07-31 09:00:00');
        $this->movimiento($sucursal->id, 'EGRESO', 900, '2026-08-12 10:00:00');
        $this->movimiento($otraSucursal->id, 'INGRESO', 800, '2026-08-12 11:00:00');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Ingresos por periodo')
            ->assertSee('data-income-period="dia"', false)
            ->assertSee('data-income-period="semana"', false)
            ->assertSee('data-income-period="mes"', false)
            ->assertViewHas('ingresosPorPeriodo', fn (array $ingresos) => $ingresos === [
                'dia' => 100.0,
                'semana' => 300.0,
                'mes' => 600.0,
            ]);
    }

    /** Crea un movimiento fechado para comprobar cada límite del periodo. */
    private function movimiento(int $sucursalId, string $tipo, float $monto, string $fecha): void
    {
        $movimiento = MovimientoCaja::create([
            'sucursal_id' => $sucursalId,
            'tipo' => $tipo,
            'categoria' => $tipo.' DE PRUEBA',
            'monto' => $monto,
            'metodo_pago' => 'efectivo',
        ]);
        $movimiento->timestamps = false;
        $movimiento->created_at = $fecha;
        $movimiento->updated_at = $fecha;
        $movimiento->save();
    }
}
