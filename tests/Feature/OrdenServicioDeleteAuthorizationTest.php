<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServicioDeleteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_does_not_see_delete_order_actions(): void
    {
        [$sucursal, $orden] = $this->crearOrden();
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);

        // El identificador exclusivo del formulario DELETE no debe renderizarse en ninguna vista.
        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index'))
            ->assertOk()
            ->assertDontSee('data-order-delete-form', false);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.show', $orden))
            ->assertOk()
            ->assertDontSee('data-order-delete-form', false);
    }

    public function test_non_superuser_roles_cannot_delete_an_order_with_a_direct_request(): void
    {
        foreach (['usuario', 'tecnico'] as $rol) {
            [$sucursal, $orden] = $this->crearOrden('ORDEN-'.$rol);
            $usuario = User::factory()->create(['rol' => $rol, 'sucursal_id' => $sucursal->id]);

            // La autorización del servidor protege el registro aunque se fabrique manualmente la petición.
            $this->actingAs($usuario)
                ->withSession(['sucursal_id' => $sucursal->id])
                ->delete(route('ordenes.destroy', $orden))
                ->assertForbidden();

            $this->assertDatabaseHas('ordenes_servicio', ['id' => $orden->id]);
        }
    }

    public function test_superuser_can_see_and_delete_an_order(): void
    {
        [$sucursal, $orden] = $this->crearOrden();
        $superusuario = User::factory()->create(['rol' => 'superusuario', 'sucursal_id' => $sucursal->id]);

        // El Super Usuario conserva la única acción visible y autorizada para eliminar órdenes.
        $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index'))
            ->assertOk()
            ->assertSee('data-order-delete-form', false);

        $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->delete(route('ordenes.destroy', $orden))
            ->assertRedirect(route('ordenes.index'));

        $this->assertDatabaseMissing('ordenes_servicio', ['id' => $orden->id]);
    }

    /**
     * Construye una orden aislada con su cliente y sucursal para probar permisos reales de borrado.
     *
     * @return array{Sucursal, OrdenServicio}
     */
    private function crearOrden(string $folio = 'ORDEN-PRUEBA'): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL '.$folio]);
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
            'problema_reportado' => 'PRUEBA DE AUTORIZACIÓN',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ]);

        return [$sucursal, $orden];
    }
}
