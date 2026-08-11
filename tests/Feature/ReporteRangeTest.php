<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
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
     * Comprueba que Valor costo se calcule desde el inventario de la sucursal activa.
     * Se conecta con cantidad_disponible, precio_costo y el resumen por proveedor.
     */
    public function test_reporte_calcula_valor_costo_desde_inventario_sin_mezclar_sucursales(): void
    {
        [$usuario, $sucursal] = $this->crearSuperusuarioConSucursal();
        $otraSucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);

        Inventario::create([
            'nombre' => 'CARGADOR PRUEBA',
            'categoria' => 'CARGADORES',
            'sucursal_id' => $sucursal->id,
            'cantidad_disponible' => 4,
            'stock_minimo' => 1,
            'precio_costo' => 75,
            'precio_venta' => 120,
            'proveedor' => 'PROVEEDOR UNO',
        ]);

        Inventario::create([
            'nombre' => 'PRODUCTO OTRA SUCURSAL',
            'categoria' => 'ACCESORIO',
            'sucursal_id' => $otraSucursal->id,
            'cantidad_disponible' => 10,
            'stock_minimo' => 1,
            'precio_costo' => 900,
            'precio_venta' => 1000,
            'proveedor' => 'PROVEEDOR AJENO',
        ]);

        $this
            ->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('reportes.index', ['periodo' => 'acumulado']))
            ->assertOk()
            ->assertSee('PROVEEDOR UNO')
            ->assertSee('$300.00')
            ->assertDontSee('PROVEEDOR AJENO');
    }

    /**
     * Comprueba que "Todas las sucursales" sume cada módulo y conserve el rango elegido.
     * Se conecta con Ventas, Órdenes, Caja, Clientes, Inventario y las gráficas de Reportes.
     */
    public function test_reporte_global_suma_todas_las_sucursales_en_un_rango_personalizado(): void
    {
        [$usuario, $izamal] = $this->crearSuperusuarioConSucursal();
        $buctzotz = Sucursal::create(['nombre' => 'BUCTZOTZ']);

        $clienteIzamal = Cliente::forceCreate([
            'nombre' => 'CLIENTE IZAMAL',
            'telefono_principal' => '9991111111',
            'sucursal_habitual_id' => $izamal->id,
            'created_at' => '2026-07-10 09:00:00',
            'updated_at' => '2026-07-10 09:00:00',
        ]);
        $clienteBuctzotz = Cliente::forceCreate([
            'nombre' => 'CLIENTE BUCTZOTZ',
            'telefono_principal' => '9992222222',
            'sucursal_habitual_id' => $buctzotz->id,
            'created_at' => '2026-07-11 09:00:00',
            'updated_at' => '2026-07-11 09:00:00',
        ]);

        $inventarioIzamal = Inventario::create([
            'nombre' => 'PRODUCTO IZAMAL',
            'categoria' => 'ACCESORIO',
            'sucursal_id' => $izamal->id,
            'cantidad_disponible' => 1,
            'stock_minimo' => 2,
            'precio_costo' => 25,
            'precio_venta' => 50,
            'proveedor' => 'PROVEEDOR IZAMAL',
        ]);
        $inventarioBuctzotz = Inventario::create([
            'nombre' => 'PRODUCTO BUCTZOTZ',
            'categoria' => 'CARGADORES',
            'sucursal_id' => $buctzotz->id,
            'cantidad_disponible' => 2,
            'stock_minimo' => 2,
            'precio_costo' => 40,
            'precio_venta' => 100,
            'proveedor' => 'PROVEEDOR BUCTZOTZ',
        ]);

        $ventaIzamal = $this->crearVenta(
            $izamal,
            100,
            '2026-07-10 12:00:00',
            $clienteIzamal->id
        );
        $ventaBuctzotz = $this->crearVenta(
            $buctzotz,
            250,
            '2026-07-11 12:00:00',
            $clienteBuctzotz->id
        );

        VentaDetalle::create([
            'venta_id' => $ventaIzamal->id,
            'inventario_id' => $inventarioIzamal->id,
            'nombre_producto' => 'PRODUCTO IZAMAL',
            'cantidad' => 2,
            'precio_unitario' => 50,
            'subtotal' => 100,
        ]);
        VentaDetalle::create([
            'venta_id' => $ventaBuctzotz->id,
            'inventario_id' => $inventarioBuctzotz->id,
            'nombre_producto' => 'PRODUCTO BUCTZOTZ',
            'cantidad' => 3,
            'precio_unitario' => 83.33,
            'subtotal' => 250,
        ]);

        $ordenIzamal = $this->crearOrden(
            $clienteIzamal,
            $izamal,
            'IZA-2026-0001',
            'RECIBIDO',
            '2026-07-10 10:00:00'
        );
        $this->crearOrden(
            $clienteBuctzotz,
            $buctzotz,
            'BUC-2026-0001',
            'ENTREGADO',
            '2026-07-11 10:00:00'
        );

        MovimientoCaja::forceCreate([
            'sucursal_id' => $izamal->id,
            'tipo' => 'INGRESO',
            'categoria' => 'VENTA',
            'monto' => 100,
            'anticipo' => 25,
            'descripcion' => 'INGRESO IZAMAL',
            'os_id' => $ordenIzamal->id,
            'created_at' => '2026-07-10 13:00:00',
            'updated_at' => '2026-07-10 13:00:00',
        ]);
        MovimientoCaja::forceCreate([
            'sucursal_id' => $buctzotz->id,
            'tipo' => 'INGRESO',
            'categoria' => 'VENTA',
            'monto' => 250,
            'descripcion' => 'INGRESO BUCTZOTZ',
            'created_at' => '2026-07-11 13:00:00',
            'updated_at' => '2026-07-11 13:00:00',
        ]);
        MovimientoCaja::forceCreate([
            'sucursal_id' => $buctzotz->id,
            'tipo' => 'EGRESO',
            'categoria' => 'GASTO',
            'monto' => 50,
            'descripcion' => 'EGRESO BUCTZOTZ',
            'created_at' => '2026-07-12 13:00:00',
            'updated_at' => '2026-07-12 13:00:00',
        ]);

        $respuesta = $this
            ->actingAs($usuario)
            ->withSession(['sucursal_id' => $izamal->id])
            ->get(route('reportes.index', [
                'periodo' => 'rango',
                'tipo_rango' => 'dia',
                'desde' => '2026-07-01',
                'hasta' => '2026-07-31',
                'todas_sucursales' => 1,
            ]));

        $respuesta
            ->assertOk()
            ->assertSee('Todas las sucursales')
            ->assertSee('PRODUCTO IZAMAL')
            ->assertSee('PRODUCTO BUCTZOTZ')
            ->assertSee('PROVEEDOR IZAMAL')
            ->assertSee('PROVEEDOR BUCTZOTZ')
            // Las cinco tarjetas financieras se muestran debajo de los indicadores operativos.
            ->assertSeeInOrder([
                'Bajo stock',
                'Total ingresos',
                'Total egresos',
                'Balance',
                'Total anticipos',
                'Total movimientos',
            ])
            ->assertViewHas('todasSucursales', true)
            ->assertViewHas('alcanceReporte', 'Todas las sucursales')
            ->assertViewHas('ventas', fn ($ventas) => $ventas->count() === 2)
            ->assertViewHas('productosExistencia', fn ($productos) => $productos->count() === 2)
            ->assertViewHas('reporteClientes', fn ($clientes) => $clientes->count() === 2)
            ->assertViewHas('reporteProveedores', fn ($proveedores) => $proveedores->count() === 2
                && (float) $proveedores->sum('valor_costo') === 105.0
            )
            ->assertViewHas('general', fn (array $general) => $general['ventas'] === 2
                && (float) $general['total_ventas'] === 350.0
                && $general['ordenes'] === 2
                && $general['clientes'] === 2
                && $general['movimientos_caja'] === 3
                && (float) $general['ingresos_caja'] === 350.0
                && (float) $general['egresos_caja'] === 50.0
                && (float) $general['balance_caja'] === 300.0
                && (float) $general['anticipos_caja'] === 25.0
                && $general['productos_bajo_stock'] === 2
            )
            ->assertViewHas('graficas', fn (array $graficas) => (float) $graficas['productos']['cantidades']->sum() === 5.0
                && (int) $graficas['ordenes']['cantidades']->sum() === 2
            );
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
    private function crearVenta(
        Sucursal $sucursal,
        float $total,
        string $fecha,
        ?int $clienteId = null
    ): Venta {
        // cliente_id conecta la operación con "Reporte por cliente"; permanece opcional
        // para que las pruebas de periodo también representen ventas sin cliente.
        return Venta::forceCreate([
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursal->id,
            'total' => $total,
            'estado' => 'completada',
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }

    /**
     * Crea una OS mínima para comprobar los totales y la gráfica de estados.
     * Se conecta con clientes, sucursales y ordenes_servicio.created_at.
     */
    private function crearOrden(
        Cliente $cliente,
        Sucursal $sucursal,
        string $numero,
        string $estado,
        string $fecha
    ): OrdenServicio {
        return OrdenServicio::forceCreate([
            'numero_os' => $numero,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'estado' => $estado,
            'marca' => 'MARCA PRUEBA',
            'modelo' => 'MODELO PRUEBA',
            'problema_reportado' => 'PROBLEMA DE PRUEBA',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }
}
