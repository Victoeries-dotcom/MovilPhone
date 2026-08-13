<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualCashMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Restablece el reloj para que esta prueba financiera no afecte a las demás.
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** Comprueba la captura y el reflejo automático del mismo registro en Caja, Panel y Reportes. */
    public function test_ingresos_y_egresos_manuales_se_reflejan_en_todos_los_resumenes(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);
        $sesion = ['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre];

        // Los botones apuntan a las rutas separadas que determinan el signo del movimiento.
        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('caja.index'))
            ->assertOk()
            ->assertSee('data-type="INGRESO"', false)
            ->assertSee('data-type="EGRESO"', false)
            ->assertSee('Corte de caja');

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('caja.ingreso'), [
                'concepto' => 'Venta de cargador fuera de mostrador',
                'monto' => 500,
                'metodo_pago' => 'efectivo',
                'origen' => 'caja',
            ])
            ->assertRedirect(route('caja.index'));

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('caja.egreso'), [
                'concepto' => 'Compra de material de limpieza',
                'monto' => 125,
                'metodo_pago' => 'transferencia',
                'origen' => 'caja',
            ])
            ->assertRedirect(route('caja.index'));

        $this->assertDatabaseHas('movimientos_caja', [
            'sucursal_id' => $sucursal->id,
            'tipo' => 'INGRESO',
            'categoria' => 'INGRESO MANUAL',
            'monto' => 500,
            'metodo_pago' => 'efectivo',
            'descripcion' => 'VENTA DE CARGADOR FUERA DE MOSTRADOR',
            'user_id' => $usuario->id,
        ]);
        $this->assertDatabaseHas('movimientos_caja', [
            'sucursal_id' => $sucursal->id,
            'tipo' => 'EGRESO',
            'categoria' => 'EGRESO MANUAL',
            'monto' => 125,
            'metodo_pago' => 'transferencia',
            'descripcion' => 'COMPRA DE MATERIAL DE LIMPIEZA',
            'user_id' => $usuario->id,
        ]);

        // Panel y Reportes consumen movimientos_caja, por eso comparten ingresos, egresos y balance.
        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('home'))
            ->assertOk()
            ->assertViewHas('indicadores', fn (array $indicadores) => $indicadores['ingresos']['actual'] === 500.0
                && $indicadores['egresos']['actual'] === 125.0
                && $indicadores['balance']['actual'] === 375.0
            );

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('reportes.index', ['periodo' => 'hoy']))
            ->assertOk()
            ->assertViewHas('general', fn (array $general) => (float) $general['ingresos_caja'] === 500.0
                && (float) $general['egresos_caja'] === 125.0
                && (float) $general['balance_caja'] === 375.0
                && $general['movimientos_caja'] === 2
            );
    }
}
