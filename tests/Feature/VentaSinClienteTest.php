<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VentaSinClienteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que Nueva venta inicia con el producto y ya no muestra datos del cliente.
     */
    public function test_formulario_de_venta_no_solicita_cliente(): void
    {
        [$usuario, $sesion] = $this->usuarioConSucursal();

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('ventas.create'))
            ->assertOk()
            ->assertSee('Paso 1 de 3')
            ->assertSee('¿Qué tipo de producto?')
            ->assertDontSee('¿Nombre del cliente?')
            ->assertDontSee('Cliente anterior')
            ->assertDontSee('name="cliente_nombre"', false)
            ->assertDontSee('name="cliente_id"', false);
    }

    /**
     * Verifica que la venta se guarda sin cliente y conserva sus detalles comerciales.
     */
    public function test_venta_se_registra_sin_cliente_y_el_listado_oculta_la_columna(): void
    {
        [$usuario, $sesion] = $this->usuarioConSucursal();

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('ventas.store'), [
                'productos' => [[
                    'nombre' => 'CAMBIO DE CENTRO DE CARGA',
                    'cantidad' => 1,
                    'precio_unitario' => 850,
                ]],
                'notas' => 'VENTA SIN DATOS DE CLIENTE',
            ])
            ->assertRedirect(route('ventas.index'));

        $venta = Venta::query()->firstOrFail();
        $this->assertNull($venta->cliente_id);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseHas('movimientos_caja', [
            'tipo' => 'INGRESO',
            'categoria' => 'Venta de productos',
            'monto' => 850,
            'referencia_pago' => 'VEN-'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT),
        ]);

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertDontSee('>Cliente</th>', false)
            ->assertSee('Producto / servicio vendido')
            ->assertSee('CAMBIO DE CENTRO DE CARGA');
    }

    /**
     * Ignora un cliente enviado manualmente para que una petición alterada no reactive el flujo eliminado.
     */
    public function test_cliente_enviado_manualmente_no_se_asocia_a_la_venta(): void
    {
        [$usuario, $sesion, $sucursal] = $this->usuarioConSucursal();
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE MANUAL',
            'telefono_principal' => '9991234567',
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('ventas.store'), [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre,
                'productos' => [[
                    'nombre' => 'PRODUCTO MANUAL',
                    'cantidad' => 1,
                    'precio_unitario' => 100,
                ]],
            ])
            ->assertRedirect(route('ventas.index'));

        $this->assertNull(Venta::query()->firstOrFail()->cliente_id);
    }

    /**
     * Comprueba que el botón quede entre Vendedor y Total y que el ticket use solo datos de la venta.
     */
    public function test_listado_y_ticket_muestran_el_comprobante_de_venta_sin_cliente(): void
    {
        [$usuario, $sesion, $sucursal] = $this->usuarioConSucursal();
        $venta = Venta::create([
            'usuario_id' => $usuario->id,
            'sucursal_id' => $sucursal->id,
            'total' => 240,
            'estado' => 'completada',
            'notas' => 'ENTREGA EN MOSTRADOR',
        ]);
        $venta->detalles()->create([
            'nombre_producto' => 'ADAPTADOR USB-C',
            'cantidad' => 2,
            'precio_unitario' => 120,
            'subtotal' => 240,
        ]);

        $listado = $this->actingAs($usuario)->withSession($sesion)->get(route('ventas.index'));
        $listado->assertOk()->assertSee(route('ventas.ticket', $venta), false);
        $html = $listado->getContent();
        $this->assertLessThan(strpos($html, '>Ticket</th>'), strpos($html, '>Vendedor</th>'));
        $this->assertLessThan(strpos($html, '>Total</th>'), strpos($html, '>Ticket</th>'));

        $ticket = $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('ventas.ticket', $venta))
            ->assertOk()
            ->assertSee('VENTA REGISTRADA')
            ->assertSee('ADAPTADOR USB-C')
            ->assertSee('2 × $120.00')
            ->assertSee('$240.00')
            ->assertSee('ENTREGA EN MOSTRADOR')
            ->assertSee('COPIA CLIENTE')
            ->assertDontSee('COPIA CAJERO')
            ->assertDontSee('---CLIENTE---')
            ->assertDontSee('NOMBRE DEL CLIENTE');

        // Una sola tarjeta imprimible garantiza que la venta entregue únicamente el ticket del cliente.
        $this->assertSame(1, substr_count($ticket->getContent(), 'class="ticket-card"'));
    }

    /**
     * Verifica que Caja ofrezca ticket para cualquier movimiento de tipo INGRESO.
     */
    public function test_caja_muestra_ticket_para_todo_ingreso(): void
    {
        [$usuario, $sesion, $sucursal] = $this->usuarioConSucursal();
        $ingreso = MovimientoCaja::create([
            'sucursal_id' => $sucursal->id,
            'tipo' => 'INGRESO',
            'categoria' => 'INGRESO MANUAL',
            'monto' => 300,
            'metodo_pago' => 'transferencia',
            'descripcion' => 'ABONO EXTRA',
            'user_id' => $usuario->id,
        ]);
        $egreso = MovimientoCaja::create([
            'sucursal_id' => $sucursal->id,
            'tipo' => 'EGRESO',
            'categoria' => 'EGRESO MANUAL',
            'monto' => 50,
            'metodo_pago' => 'efectivo',
            'descripcion' => 'COMPRA MENOR',
            'user_id' => $usuario->id,
        ]);

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('caja.index'))
            ->assertOk()
            ->assertSee(route('caja.ticket', $ingreso), false)
            ->assertDontSee(route('caja.ticket', $egreso), false);

        $ticket = $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('caja.ticket', $ingreso))
            ->assertOk()
            ->assertSee('ABONO EXTRA')
            ->assertSee('$300.00')
            ->assertSee('COPIA CLIENTE')
            ->assertSee('COPIA CAJERA');

        // Cada bloque cash-ticket representa una hoja completa enviada a la impresora.
        $this->assertSame(2, substr_count($ticket->getContent(), 'class="cash-ticket"'));
        $this->assertSame(2, substr_count($ticket->getContent(), 'class="cash-ticket-print-page"'));
        $ticket->assertSee('zoom:50%', false);
    }

    /**
     * Impide consultar tickets de ventas pertenecientes a una sucursal diferente.
     */
    public function test_ticket_de_venta_respeta_la_sucursal_activa(): void
    {
        [$usuario, $sesion] = $this->usuarioConSucursal();
        $otraSucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $ventaAjena = Venta::create([
            'usuario_id' => $usuario->id,
            'sucursal_id' => $otraSucursal->id,
            'total' => 100,
            'estado' => 'completada',
        ]);

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('ventas.ticket', $ventaAjena))
            ->assertNotFound();
    }

    /**
     * Confirma que la garantía configurada aparezca en ambas copias antes de cada folio.
     */
    public function test_ticket_de_venta_incluye_garantia_en_copia_cliente_y_cajero(): void
    {
        [$usuario, $sesion, $sucursal] = $this->usuarioConSucursal();
        $politica = 'GARANTÍA PERSONALIZADA PARA PRODUCTOS Y SERVICIOS VENDIDOS.';
        DB::table('configuraciones')->insert([
            'clave' => 'politica_garantia',
            'valor' => $politica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $venta = Venta::create([
            'usuario_id' => $usuario->id,
            'sucursal_id' => $sucursal->id,
            'total' => 150,
            'estado' => 'completada',
        ]);

        $contenido = $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('ventas.ticket', $venta))
            ->assertOk()
            ->assertSee('POLÍTICA DE GARANTÍA')
            ->assertSee($politica)
            ->getContent();

        $this->assertSame(2, substr_count($contenido, $politica));
        $this->assertLessThan(strpos($contenido, '<div>Folio: #'), strpos($contenido, $politica));
    }

    /**
     * Crea el usuario, la sucursal activa y la sesión compartida por las pruebas de Ventas.
     *
     * @return array{0: User, 1: array<string, int|string>, 2: Sucursal}
     */
    private function usuarioConSucursal(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
        ]);
        $sesion = [
            'sucursal_id' => $sucursal->id,
            'sucursal_nombre' => $sucursal->nombre,
        ];

        return [$usuario, $sesion, $sucursal];
    }
}
