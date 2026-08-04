<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServicioPeriodFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_orders_by_selected_day_and_active_branch(): void
    {
        [$usuario, $sucursal] = $this->crearUsuarioYSucursal('usuario');
        $otraSucursal = Sucursal::create(['nombre' => 'OTRA SUCURSAL']);

        $this->crearOrden($sucursal, 'DIA-VISIBLE', '2026-08-04 10:00:00');
        $this->crearOrden($sucursal, 'DIA-OCULTA', '2026-08-03 10:00:00');
        $this->crearOrden($otraSucursal, 'OTRA-SUCURSAL', '2026-08-04 10:00:00');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index', ['periodo' => 'dia', 'periodo_valor' => '2026-08-04']))
            ->assertOk()
            ->assertSee('DIA-VISIBLE')
            ->assertDontSee('DIA-OCULTA')
            ->assertDontSee('OTRA-SUCURSAL');
    }

    public function test_filters_orders_by_selected_iso_week(): void
    {
        [$usuario, $sucursal] = $this->crearUsuarioYSucursal('usuario');

        $this->crearOrden($sucursal, 'SEMANA-VISIBLE', '2026-08-05 10:00:00');
        $this->crearOrden($sucursal, 'SEMANA-OCULTA', '2026-08-10 10:00:00');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index', ['periodo' => 'semana', 'periodo_valor' => '2026-W32']))
            ->assertOk()
            ->assertSee('SEMANA-VISIBLE')
            ->assertDontSee('SEMANA-OCULTA');
    }

    public function test_filters_orders_by_selected_month_for_superuser(): void
    {
        [$superusuario, $sucursal] = $this->crearUsuarioYSucursal('superusuario');

        $this->crearOrden($sucursal, 'MES-VISIBLE', '2026-08-31 23:59:00');
        $this->crearOrden($sucursal, 'MES-OCULTA', '2026-09-01 00:00:00');

        $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index', ['periodo' => 'mes', 'periodo_valor' => '2026-08']))
            ->assertOk()
            ->assertSee('MES-VISIBLE')
            ->assertDontSee('MES-OCULTA');
    }

    public function test_rejects_an_impossible_selected_day(): void
    {
        [$usuario, $sucursal] = $this->crearUsuarioYSucursal('usuario');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index', ['periodo' => 'dia', 'periodo_valor' => '2026-02-31']))
            ->assertSessionHasErrors('periodo_valor');
    }

    private function crearUsuarioYSucursal(string $rol): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL FILTRO']);
        $usuario = User::factory()->create(['rol' => $rol, 'sucursal_id' => $sucursal->id]);

        return [$usuario, $sucursal];
    }

    private function crearOrden(Sucursal $sucursal, string $folio, string $fecha): OrdenServicio
    {
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE '.$folio,
            'telefono_principal' => '999'.str_pad((string) Cliente::count(), 7, '0', STR_PAD_LEFT),
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        $orden = OrdenServicio::create([
            'numero_os' => $folio,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'estado' => 'RECIBIDO',
            'tipo_dispositivo' => 'TELÉFONO',
            'marca' => 'MARCA',
            'modelo' => 'MODELO',
            'problema_reportado' => 'PRUEBA DE FILTRO',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ]);

        // created_at determina si la orden pertenece al día, semana o mes solicitado.
        $orden->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();

        return $orden;
    }
}
