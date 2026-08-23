<?php

namespace Tests\Feature;

use App\Models\AdminActivity;
use App\Models\Cliente;
use App\Models\MovimientoCaja;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardIncomePeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Evita que la fecha simulada de estos indicadores afecte otras pruebas del sistema.
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** Verifica que el resumen financiero diario no mezcle fechas, tipos u otras sucursales. */
    public function test_panel_calcula_ingresos_egresos_y_balance_para_la_sucursal_activa(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 15:00:00');
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $otraSucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);

        $this->movimiento($sucursal->id, 'INGRESO', 100, '2026-08-12 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 60, '2026-08-12 09:30:00', 'transferencia');
        $this->movimiento($sucursal->id, 'INGRESO', 40, '2026-08-12 09:45:00', 'tarjeta');
        $this->movimiento($sucursal->id, 'INGRESO', 200, '2026-08-10 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 300, '2026-08-02 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 400, '2026-07-31 09:00:00');
        $this->movimiento($sucursal->id, 'EGRESO', 900, '2026-08-12 10:00:00');
        $this->movimiento($otraSucursal->id, 'INGRESO', 800, '2026-08-12 11:00:00');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Efectivo en caja')
            ->assertSee('Transferencias recibidas')
            ->assertSee('Pagos con tarjeta')
            ->assertSee('Total egresos')
            ->assertSee('Balance')
            ->assertDontSee('Ingresos por periodo')
            ->assertDontSee('data-income-period', false)
            ->assertViewHas('indicadores', fn (array $indicadores) => $indicadores['ingresos']['actual'] === 200.0
                && $indicadores['efectivo']['actual'] === 100.0
                && $indicadores['transferencias']['actual'] === 60.0
                && $indicadores['tarjeta']['actual'] === 40.0
                && $indicadores['egresos']['actual'] === 900.0
                && $indicadores['balance']['actual'] === -700.0
            );
    }

    /**
     * Confirma que un mismo ingreso de movimientos_caja alimenta Reportes y el Panel principal.
     */
    public function test_ingreso_registrado_se_refleja_en_reportes_y_panel_principal(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 15:00:00');
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $superusuario = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);
        $sesionSucursal = [
            'sucursal_id' => $sucursal->id,
            'sucursal_nombre' => $sucursal->nombre,
        ];

        $this->movimiento($sucursal->id, 'INGRESO', 175, '2026-08-12 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 100, '2026-08-12 09:30:00', 'transferencia');
        $this->movimiento($sucursal->id, 'INGRESO', 50, '2026-08-12 09:45:00', 'tarjeta');
        $this->movimiento($sucursal->id, 'EGRESO', 40, '2026-08-12 10:00:00');

        $this->actingAs($superusuario)
            ->withSession($sesionSucursal)
            ->get(route('home'))
            ->assertOk()
            ->assertViewHas('indicadores', fn (array $indicadores) => $indicadores['ingresos']['actual'] === 325.0
                && $indicadores['efectivo']['actual'] === 175.0
                && $indicadores['transferencias']['actual'] === 100.0
                && $indicadores['tarjeta']['actual'] === 50.0
                && $indicadores['egresos']['actual'] === 40.0
                && $indicadores['balance']['actual'] === 285.0
            );

        $this->actingAs($superusuario)
            ->withSession($sesionSucursal)
            ->get(route('reportes.index', ['periodo' => 'hoy']))
            ->assertOk()
            ->assertSee('Caja y Finanzas')
            ->assertViewHas('general', fn (array $general) => (float) $general['ingresos_caja'] === 325.0
                && (float) $general['efectivo_caja'] === 175.0
                && (float) $general['transferencias_caja'] === 100.0
                && (float) $general['tarjeta_caja'] === 50.0
                && (float) $general['egresos_caja'] === 40.0
                && (float) $general['balance_caja'] === 285.0
                && $general['movimientos_caja'] === 4
            );
    }

    /**
     * Comprueba que Supervisión por día cambie las cifras sin mezclar el día actual ni otra sucursal.
     */
    public function test_panel_permite_supervisar_un_dia_del_mes_actual(): void
    {
        CarbonImmutable::setTestNow('2026-08-23 15:00:00');
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ OFICIAL']);
        $otraSucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);

        // El 7 de agosto es el día solicitado y el 6 funciona como comparación inmediata.
        $this->movimiento($sucursal->id, 'INGRESO', 120, '2026-08-07 09:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 30, '2026-08-07 10:00:00', 'transferencia');
        $this->movimiento($sucursal->id, 'EGRESO', 15, '2026-08-07 11:00:00');
        $this->movimiento($sucursal->id, 'INGRESO', 50, '2026-08-06 09:00:00');

        // Estos movimientos demuestran que el filtro excluye hoy y la sucursal ajena.
        $this->movimiento($sucursal->id, 'INGRESO', 999, '2026-08-23 09:00:00');
        $this->movimiento($otraSucursal->id, 'INGRESO', 700, '2026-08-07 09:00:00');

        $clienteDia = Cliente::create([
            'nombre' => 'CLIENTE DEL DÍA',
            'telefono_principal' => '9911000001',
            'sucursal_habitual_id' => $sucursal->id,
        ]);
        $this->fechar($clienteDia, '2026-08-07 08:00:00');

        $ordenDia = OrdenServicio::create([
            'numero_os' => 'OS-SUPERVISION-07',
            'cliente_id' => $clienteDia->id,
            'sucursal_id' => $sucursal->id,
            'estado' => 'RECIBIDO',
            'marca' => 'SAMSUNG',
            'modelo' => 'A15',
            'problema_reportado' => 'PRUEBA DEL DÍA',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ]);
        $this->fechar($ordenDia, '2026-08-07 08:30:00');

        $ordenHoy = OrdenServicio::create([
            'numero_os' => 'OS-FUERA-23',
            'cliente_id' => $clienteDia->id,
            'sucursal_id' => $sucursal->id,
            'estado' => 'RECIBIDO',
            'marca' => 'APPLE',
            'modelo' => 'IPHONE 13',
            'problema_reportado' => 'PRUEBA DE HOY',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ]);
        $this->fechar($ordenHoy, '2026-08-23 08:30:00');

        $actividadDia = AdminActivity::create([
            'modulo' => 'CAJA',
            'accion' => 'REGISTRO SUPERVISADO',
            'descripcion' => 'ACTIVIDAD DEL SIETE DE AGOSTO',
            'sucursal_id' => $sucursal->id,
            'user_id' => $usuario->id,
        ]);
        $this->fechar($actividadDia, '2026-08-07 12:00:00');

        $actividadHoy = AdminActivity::create([
            'modulo' => 'CAJA',
            'accion' => 'REGISTRO ACTUAL',
            'descripcion' => 'ACTIVIDAD DEL DÍA ACTUAL',
            'sucursal_id' => $sucursal->id,
            'user_id' => $usuario->id,
        ]);
        $this->fechar($actividadHoy, '2026-08-23 12:00:00');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre])
            ->get(route('home', ['fecha_supervision' => '2026-08-07']))
            ->assertOk()
            ->assertSee('Supervisión por día')
            ->assertSee('name="fecha_supervision"', false)
            ->assertSee('min="2026-08-01"', false)
            ->assertSee('max="2026-08-23"', false)
            ->assertSee('value="2026-08-07"', false)
            ->assertViewHas('diaPanel', fn (CarbonImmutable $dia) => $dia->toDateString() === '2026-08-07')
            ->assertViewHas('supervisionActiva', true)
            ->assertViewHas('ordenesRecientes', fn ($ordenes) => $ordenes->pluck('numero_os')->all() === ['OS-SUPERVISION-07'])
            ->assertViewHas('actividadReciente', fn ($actividades) => $actividades->pluck('accion')->all() === ['REGISTRO SUPERVISADO'])
            ->assertViewHas('indicadores', fn (array $indicadores) => $indicadores['ingresos']['actual'] === 150.0
                && $indicadores['ingresos']['anterior'] === 50.0
                && $indicadores['efectivo']['actual'] === 120.0
                && $indicadores['transferencias']['actual'] === 30.0
                && $indicadores['egresos']['actual'] === 15.0
                && $indicadores['balance']['actual'] === 135.0
            );
    }

    /** Impide consultar días de otro mes o fechas futuras que todavía no tienen operación. */
    public function test_panel_rechaza_dias_fuera_del_mes_actual(): void
    {
        CarbonImmutable::setTestNow('2026-08-23 15:00:00');
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ OFICIAL']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);
        $sesion = ['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre];

        foreach (['2026-07-31', '2026-08-24', 'fecha-invalida'] as $fechaInvalida) {
            $this->actingAs($usuario)
                ->withSession($sesion)
                ->from(route('home'))
                ->get(route('home', ['fecha_supervision' => $fechaInvalida]))
                ->assertRedirect(route('home'))
                ->assertSessionHasErrors('fecha_supervision');
        }
    }

    /** Crea un movimiento fechado para comprobar cada límite del periodo. */
    private function movimiento(
        int $sucursalId,
        string $tipo,
        float $monto,
        string $fecha,
        string $metodoPago = 'efectivo'
    ): void {
        $movimiento = MovimientoCaja::create([
            'sucursal_id' => $sucursalId,
            'tipo' => $tipo,
            'categoria' => $tipo.' DE PRUEBA',
            'monto' => $monto,
            // El método permite demostrar que efectivo y transferencia se suman por separado.
            'metodo_pago' => $metodoPago,
        ]);
        $movimiento->timestamps = false;
        $movimiento->created_at = $fecha;
        $movimiento->updated_at = $fecha;
        $movimiento->save();
    }

    /** Ajusta las marcas de tiempo de un registro para probar la consulta histórica del panel. */
    private function fechar(Model $modelo, string $fecha): void
    {
        $modelo->timestamps = false;
        $modelo->created_at = $fecha;
        $modelo->updated_at = $fecha;
        $modelo->save();
    }
}
