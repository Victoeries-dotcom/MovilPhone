<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\MovimientoCaja;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que Usuario solo vea clientes, órdenes, inventario y caja de su sucursal.
     * Se conecta con los controladores operativos y evita mezclar Buctzotz con Izamal.
     */
    public function test_usuario_solo_ve_registros_de_su_sucursal(): void
    {
        [$buctzotz, $izamal, $usuario] = $this->crearContextoDeSucursales();

        $clienteBuctzotz = Cliente::create([
            'nombre' => 'CLIENTE EXCLUSIVO BUCTZOTZ',
            'telefono_principal' => '9911000001',
            'sucursal_habitual_id' => $buctzotz->id,
        ]);
        $clienteIzamal = Cliente::create([
            'nombre' => 'CLIENTE EXCLUSIVO IZAMAL',
            'telefono_principal' => '9911000002',
            'sucursal_habitual_id' => $izamal->id,
        ]);

        OrdenServicio::create($this->datosOrden(
            'BUC-PRUEBA-0001',
            $clienteBuctzotz->id,
            $buctzotz->id,
            'PROBLEMA SOLO BUCTZOTZ'
        ));
        OrdenServicio::create($this->datosOrden(
            'IZA-PRUEBA-0001',
            $clienteIzamal->id,
            $izamal->id,
            'PROBLEMA SOLO IZAMAL'
        ));

        Inventario::create($this->datosInventario('PIEZA SOLO BUCTZOTZ', $buctzotz->id));
        Inventario::create(array_merge(
            $this->datosInventario('PIEZA OTRA CATEGORIA BUCTZOTZ', $buctzotz->id),
            ['categoria' => 'OTRA CATEGORIA']
        ));
        Inventario::create($this->datosInventario('PIEZA SOLO IZAMAL', $izamal->id));

        MovimientoCaja::create($this->datosMovimiento('MOVIMIENTO SOLO BUCTZOTZ', $buctzotz->id, $usuario->id));
        MovimientoCaja::create($this->datosMovimiento('MOVIMIENTO SOLO IZAMAL', $izamal->id, $usuario->id));

        $sesion = [
            'sucursal_id' => $buctzotz->id,
            'sucursal_nombre' => $buctzotz->nombre,
        ];

        $this->actingAs($usuario)->withSession($sesion)->get(route('clientes.index'))
            ->assertOk()
            ->assertSee('CLIENTE EXCLUSIVO BUCTZOTZ')
            ->assertDontSee('CLIENTE EXCLUSIVO IZAMAL');

        $this->actingAs($usuario)->withSession($sesion)->get(route('ordenes.index'))
            ->assertOk()
            ->assertSee('BUC-PRUEBA-0001')
            ->assertDontSee('IZA-PRUEBA-0001')
            // Comprueba que la nueva cabecera operativa conecte solo con rutas autorizadas para Usuario.
            ->assertSee('Vender productos')
            ->assertSee(route('ventas.create'), false)
            ->assertSee(route('ordenes.index', ['estado' => 'GARANTÍA']), false)
            ->assertSee('orders-filter-panel', false);

        $this->actingAs($usuario)->withSession($sesion)->get(route('inventario.index'))
            ->assertOk()
            // Comprueba que la nueva interfaz reciba los indicadores y controles conectados al inventario de la sede.
            ->assertSee('Control de productos')
            ->assertSee('Unidades en existencia')
            // Dos productos de Buctzotz: 3 unidades x $150 cada uno = $900.
            // Confirma que el valor use precio_venta y no incluya la pieza perteneciente a Izamal.
            ->assertSee('$900.00')
            ->assertSee('Categorías')
            ->assertSee('inventory-filter-panel', false)
            ->assertSee('PIEZA SOLO BUCTZOTZ')
            ->assertDontSee('PIEZA SOLO IZAMAL');

        // Verifica que las pestañas de categoría filtren dentro de Buctzotz sin mostrar otras categorías ni sucursales.
        $this->actingAs($usuario)->withSession($sesion)->get(route('inventario.index', ['categoria' => 'PRUEBA']))
            ->assertOk()
            ->assertSee('PIEZA SOLO BUCTZOTZ')
            ->assertDontSee('PIEZA OTRA CATEGORIA BUCTZOTZ')
            ->assertDontSee('PIEZA SOLO IZAMAL');

        $this->actingAs($usuario)->withSession($sesion)->get(route('caja.index'))
            ->assertOk()
            // Comprueba que la interfaz profesional mantenga filtros y tabla sin el alta manual solicitada.
            ->assertSee('cash-filter-panel', false)
            ->assertSee('cash-table-panel', false)
            ->assertDontSee('Registrar movimiento')
            ->assertSee('MOVIMIENTO SOLO BUCTZOTZ')
            ->assertDontSee('MOVIMIENTO SOLO IZAMAL');
    }

    /**
     * Verifica que el superusuario conserve Corte de caja sin mostrar Registrar movimiento.
     * Se conecta con caja.index y con la autorización exclusiva definida en routes/web.php.
     */
    public function test_caja_muestra_solo_corte_al_superusuario(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $superusuario = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        $this
            ->actingAs($superusuario)
            ->withSession([
                'sucursal_id' => $sucursal->id,
                'sucursal_nombre' => $sucursal->nombre,
            ])
            ->get(route('caja.index'))
            ->assertOk()
            ->assertSee('Corte de caja')
            ->assertSee(route('caja.corte'), false)
            ->assertDontSee('Registrar movimiento');
    }

    /**
     * Impide borrar desde Caja un cobro automático generado por una venta.
     * Se conecta con MovimientoCajaController::destroy y protege la consistencia con Ventas.
     */
    public function test_caja_no_elimina_movimientos_ligados_a_ventas(): void
    {
        [$buctzotz, , $usuario] = $this->crearContextoDeSucursales();
        $movimiento = MovimientoCaja::create(array_merge(
            $this->datosMovimiento('VENTA PROTEGIDA', $buctzotz->id, $usuario->id),
            ['categoria' => 'Venta de productos']
        ));

        $this
            ->actingAs($usuario)
            ->withSession([
                'sucursal_id' => $buctzotz->id,
                'sucursal_nombre' => $buctzotz->nombre,
            ])
            ->delete(route('caja.destroy', $movimiento))
            ->assertRedirect(route('caja.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('movimientos_caja', [
            'id' => $movimiento->id,
            'categoria' => 'Venta de productos',
        ]);
    }

    /**
     * Comprueba que Caja ignore una sucursal manipulada desde el navegador.
     * Se conecta con MovimientoCajaController y guarda siempre en la sucursal activa.
     */
    public function test_movimiento_manual_se_guarda_en_la_sucursal_activa(): void
    {
        [$buctzotz, $izamal, $usuario] = $this->crearContextoDeSucursales();

        $this
            ->actingAs($usuario)
            ->withSession([
                'sucursal_id' => $buctzotz->id,
                'sucursal_nombre' => $buctzotz->nombre,
            ])
            ->post(route('caja.store'), [
                // Simula una alteración del formulario; el backend debe ignorar esta sede.
                'sucursal_id' => $izamal->id,
                'tipo' => 'INGRESO',
                'categoria' => 'SERVICIO MANUAL',
                'monto' => 350,
                'metodo_pago' => 'efectivo',
                'descripcion' => 'PRUEBA DE AISLAMIENTO',
            ])
            ->assertRedirect(route('caja.index'));

        $this->assertDatabaseHas('movimientos_caja', [
            'sucursal_id' => $buctzotz->id,
            'descripcion' => 'PRUEBA DE AISLAMIENTO',
        ]);
        $this->assertDatabaseMissing('movimientos_caja', [
            'sucursal_id' => $izamal->id,
            'descripcion' => 'PRUEBA DE AISLAMIENTO',
        ]);
    }

    /**
     * Verifica que una OS no pueda recibir un técnico de otra sucursal por petición manual.
     * Se conecta con la validación de OrdenServicioController y users.sucursal_id.
     */
    public function test_orden_rechaza_un_tecnico_de_otra_sucursal(): void
    {
        [$buctzotz, $izamal, $usuario] = $this->crearContextoDeSucursales();
        $tecnicoIzamal = User::factory()->create([
            'rol' => 'tecnico',
            'sucursal_id' => $izamal->id,
        ]);

        $this
            ->actingAs($usuario)
            ->withSession([
                'sucursal_id' => $buctzotz->id,
                'sucursal_nombre' => $buctzotz->nombre,
            ])
            ->from(route('ordenes.create'))
            ->post(route('ordenes.store'), [
                'cliente_nombre' => 'CLIENTE DE VALIDACION',
                'cliente_telefono' => '9911000099',
                'sucursal_id' => $buctzotz->id,
                // El ID pertenece a Izamal y debe ser rechazado antes de crear la OS.
                'tecnico_id' => $tecnicoIzamal->id,
                'tipo_dispositivo' => 'TELEFONO',
                'marca' => 'MARCA PRUEBA',
                'modelo' => 'MODELO PRUEBA',
                'problema_reportado' => 'PRUEBA DE SUCURSAL',
                'estado_fisico' => 'BUENO',
            ])
            ->assertRedirect(route('ordenes.create'))
            ->assertSessionHasErrors('tecnico_id');

        $this->assertDatabaseMissing('ordenes_servicio', [
            'sucursal_id' => $buctzotz->id,
            'tecnico_id' => $tecnicoIzamal->id,
        ]);
    }

    /**
     * Crea las dos sucursales y un Usuario asignado a Buctzotz.
     * Se reutiliza para que las pruebas representen el flujo real de acceso.
     */
    private function crearContextoDeSucursales(): array
    {
        $buctzotz = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $izamal = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $buctzotz->id,
        ]);

        return [$buctzotz, $izamal, $usuario];
    }

    /**
     * Proporciona los campos mínimos de una OS para probar su filtrado.
     * Se conecta con ordenes_servicio y con la relación del cliente.
     */
    private function datosOrden(string $numero, int $clienteId, int $sucursalId, string $problema): array
    {
        return [
            'numero_os' => $numero,
            'cliente_id' => $clienteId,
            'sucursal_id' => $sucursalId,
            'estado' => 'RECIBIDO',
            'marca' => 'MARCA PRUEBA',
            'modelo' => 'MODELO PRUEBA',
            'problema_reportado' => $problema,
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ];
    }

    /**
     * Proporciona una pieza reconocible para comprobar el inventario por sede.
     * Se conecta con inventario.sucursal_id y los indicadores del listado.
     */
    private function datosInventario(string $nombre, int $sucursalId): array
    {
        return [
            'nombre' => $nombre,
            'categoria' => 'PRUEBA',
            'sucursal_id' => $sucursalId,
            'cantidad_disponible' => 3,
            'stock_minimo' => 1,
            'precio_costo' => 100,
            'precio_venta' => 150,
        ];
    }

    /**
     * Proporciona un movimiento financiero identificable para probar Caja.
     * Se conecta con movimientos_caja, la sucursal y el usuario que lo registró.
     */
    private function datosMovimiento(string $descripcion, int $sucursalId, int $usuarioId): array
    {
        return [
            'sucursal_id' => $sucursalId,
            'tipo' => 'INGRESO',
            'categoria' => 'PRUEBA',
            'monto' => 100,
            'metodo_pago' => 'efectivo',
            'descripcion' => $descripcion,
            'user_id' => $usuarioId,
        ];
    }
}
