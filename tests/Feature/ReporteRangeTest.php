<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteRangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Comprueba que un rango de días incluya solo ventas de la sucursal y fechas elegidas.
     * Se conecta con ReporteController, la sesión de sucursal y la tarjeta Total vendido.
     */
    public function test_reporte_filtra_un_intervalo_personalizado_de_dias(): void
    {
        [$usuario, $sucursal] = $this->crearSuperusuarioConSucursal();

        $this->crearVenta($sucursal, 125, '2026-07-10 12:00:00');
        $this->crearVenta($sucursal, 900, '2026-07-20 12:00:00');

        $respuesta = $this
            ->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('reportes.index', [
                'periodo' => 'rango',
                'tipo_rango' => 'dia',
                'desde' => '2026-07-05',
                'hasta' => '2026-07-15',
            ]));

        $respuesta
            ->assertOk()
            ->assertSee('Actividad del 05/07/2026 al 15/07/2026')
            ->assertViewHas('general', fn (array $general) => $general['ventas_mostradas'] === 1
                && (float) $general['total_ventas_mostrado'] === 125.0
                && $general['ventas_es_acumulado'] === false
            );
    }

    /**
     * Verifica que una semana ISO se expanda de lunes a domingo completos.
     * Se conecta con los controles input week y los límites created_at del reporte.
     */
    public function test_reporte_convierte_semanas_en_sus_limites_completos(): void
    {
        [$usuario, $sucursal] = $this->crearSuperusuarioConSucursal();

        $respuesta = $this
            ->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('reportes.index', [
                'periodo' => 'rango',
                'tipo_rango' => 'semana',
                'desde' => '2026-W29',
                'hasta' => '2026-W30',
            ]));

        $respuesta
            ->assertOk()
            ->assertViewHas('inicio', fn ($inicio) => $inicio->format('Y-m-d H:i:s') === '2026-07-13 00:00:00')
            ->assertViewHas('fin', fn ($fin) => $fin->format('Y-m-d H:i:s') === '2026-07-26 23:59:59');
    }

    /**
     * Verifica que el selector mensual abarque desde el primer día hasta el último.
     * Se conecta con los controles input month y todas las consultas del periodo.
     */
    public function test_reporte_convierte_meses_en_sus_limites_completos(): void
    {
        [$usuario, $sucursal] = $this->crearSuperusuarioConSucursal();

        $respuesta = $this
            ->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('reportes.index', [
                'periodo' => 'rango',
                'tipo_rango' => 'mes',
                'desde' => '2026-06',
                'hasta' => '2026-07',
            ]));

        $respuesta
            ->assertOk()
            ->assertViewHas('inicio', fn ($inicio) => $inicio->format('Y-m-d H:i:s') === '2026-06-01 00:00:00')
            ->assertViewHas('fin', fn ($fin) => $fin->format('Y-m-d H:i:s') === '2026-07-31 23:59:59');
    }

    /**
     * Evita consultar un periodo final anterior al inicial.
     * Se conecta con la validación del formulario y devuelve el error junto al campo Hasta.
     */
    public function test_reporte_rechaza_un_rango_invertido(): void
    {
        [$usuario, $sucursal] = $this->crearSuperusuarioConSucursal();

        $this
            ->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->from(route('reportes.index'))
            ->get(route('reportes.index', [
                'periodo' => 'rango',
                'tipo_rango' => 'dia',
                'desde' => '2026-07-20',
                'hasta' => '2026-07-10',
            ]))
            ->assertRedirect(route('reportes.index'))
            ->assertSessionHasErrors('hasta');
    }

    /**
     * Prepara el contexto mínimo autorizado para abrir Reportes.
     * Se conecta con RoleMiddleware y con la sucursal activa guardada en sesión.
     */
    private function crearSuperusuarioConSucursal(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        return [$usuario, $sucursal];
    }

    /**
     * Inserta una venta en una fecha controlada para probar los límites del reporte.
     * Se conecta directamente con ventas.created_at y la sucursal preparada por la prueba.
     */
    private function crearVenta(Sucursal $sucursal, float $total, string $fecha): Venta
    {
        return Venta::forceCreate([
            'sucursal_id' => $sucursal->id,
            'total' => $total,
            'estado' => 'completada',
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }
}
