<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
