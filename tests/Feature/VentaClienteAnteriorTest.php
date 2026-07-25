<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaClienteAnteriorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que usuarios y superusuarios recuperan al cliente por teléfono.
     * Se conecta con ventas.buscarClientePorTelefono y clientes.telefono_normalizado.
     */
    public function test_usuario_y_superusuario_pueden_buscar_un_cliente_anterior_de_la_sucursal_activa(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE ANTERIOR',
            'telefono_principal' => '991-106-4338',
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        foreach (['usuario', 'superusuario'] as $rol) {
            $usuario = User::factory()->create([
                'rol' => $rol,
                'sucursal_id' => $rol === 'usuario' ? $sucursal->id : null,
            ]);

            $this
                ->actingAs($usuario)
                ->withSession([
                    'sucursal_id' => $sucursal->id,
                    'sucursal_nombre' => $sucursal->nombre,
                ])
                ->getJson(route('ventas.buscarClientePorTelefono', ['telefono' => '9911064338']))
                ->assertOk()
                ->assertJsonPath('cliente.id', $cliente->id)
                ->assertJsonPath('cliente.nombre', 'CLIENTE ANTERIOR');
        }
    }

    /**
     * Verifica que la venta reutiliza clientes.id y muestra su producto en la tabla.
     * Se conecta con ventas.cliente_id, venta_detalles y la vista ventas.index.
     */
    public function test_venta_reutiliza_cliente_anterior_y_muestra_producto_o_servicio_vendido(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
        ]);
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE FRECUENTE',
            'telefono_principal' => '9991234567',
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        $this
            ->actingAs($usuario)
            ->withSession([
                'sucursal_id' => $sucursal->id,
                'sucursal_nombre' => $sucursal->nombre,
            ])
            ->post(route('ventas.store'), [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre,
                'productos' => [[
                    'nombre' => 'CAMBIO DE CENTRO DE CARGA',
                    'cantidad' => 1,
                    'precio_unitario' => 850,
                ]],
                'notas' => 'VENTA PARA CLIENTE ANTERIOR',
            ])
            ->assertRedirect(route('ventas.index'));

        $venta = Venta::query()->firstOrFail();
        $this->assertSame($cliente->id, $venta->cliente_id);
        $this->assertDatabaseCount('clientes', 1);

        $this
            ->actingAs($usuario)
            ->withSession([
                'sucursal_id' => $sucursal->id,
                'sucursal_nombre' => $sucursal->nombre,
            ])
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Producto / servicio vendido')
            ->assertSee('CAMBIO DE CENTRO DE CARGA');
    }

    /**
     * Impide que una búsqueda o petición manual reutilice clientes de otra sede.
     * Se conecta con sucursal_habitual_id y la sucursal almacenada en sesión.
     */
    public function test_cliente_anterior_de_otra_sucursal_no_puede_usarse_en_la_venta(): void
    {
        $buctzotz = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $izamal = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $buctzotz->id,
        ]);
        $clienteIzamal = Cliente::create([
            'nombre' => 'CLIENTE IZAMAL',
            'telefono_principal' => '9990001111',
            'sucursal_habitual_id' => $izamal->id,
        ]);

        $sesionBuctzotz = [
            'sucursal_id' => $buctzotz->id,
            'sucursal_nombre' => $buctzotz->nombre,
        ];

        $this
            ->actingAs($usuario)
            ->withSession($sesionBuctzotz)
            ->getJson(route('ventas.buscarClientePorTelefono', ['telefono' => '9990001111']))
            ->assertNotFound();

        $this
            ->actingAs($usuario)
            ->withSession($sesionBuctzotz)
            ->from(route('ventas.create'))
            ->post(route('ventas.store'), [
                'cliente_id' => $clienteIzamal->id,
                'cliente_nombre' => $clienteIzamal->nombre,
                'productos' => [[
                    'nombre' => 'SERVICIO NO AUTORIZADO',
                    'cantidad' => 1,
                    'precio_unitario' => 100,
                ]],
            ])
            ->assertRedirect(route('ventas.create'))
            ->assertSessionHasErrors('cliente_id');

        $this->assertDatabaseCount('ventas', 0);
    }
}
