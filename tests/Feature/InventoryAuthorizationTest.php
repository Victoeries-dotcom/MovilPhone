<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** El rol usuario puede consultar, agregar y editar productos, pero no eliminarlos. */
    public function test_usuario_puede_editar_productos_sin_eliminarlos(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);
        $producto = Inventario::create($this->datosProducto('PRODUCTO PROTEGIDO', $sucursal->id));
        $sesion = ['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre];

        $this->actingAs($usuario)->withSession($sesion)->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('Agregar producto')
            ->assertSee(route('inventario.create'), false)
            // Busca los controles reales para distinguir Editar de la acción destructiva.
            ->assertSee(route('inventario.edit', $producto), false)
            ->assertSee('inventory-edit-button', false)
            ->assertDontSee('inventory-delete-button', false);

        // El formulario y la actualización directa quedan autorizados para la sucursal activa.
        $this->actingAs($usuario)->withSession($sesion)->get(route('inventario.edit', $producto))
            ->assertOk();
        $this->actingAs($usuario)->withSession($sesion)->put(route('inventario.update', $producto), [
            ...$this->datosProducto('PRODUCTO EDITADO', $sucursal->id),
        ])->assertRedirect(route('inventario.index'));

        $this->assertDatabaseHas('inventario', [
            'id' => $producto->id,
            'nombre' => 'PRODUCTO EDITADO',
        ]);

        // El backend conserva la eliminación fuera del permiso de Usuario.
        $this->actingAs($usuario)->withSession($sesion)->delete(route('inventario.destroy', $producto))
            ->assertForbidden();

        $this->assertDatabaseHas('inventario', [
            'id' => $producto->id,
            'nombre' => 'PRODUCTO EDITADO',
        ]);

        // El alta permanece habilitada y el servidor conserva la sucursal activa del usuario.
        $this->actingAs($usuario)->withSession($sesion)->post(route('inventario.store'), [
            ...$this->datosProducto('PRODUCTO NUEVO', $sucursal->id),
        ])->assertRedirect(route('inventario.index'));

        $this->assertDatabaseHas('inventario', [
            'nombre' => 'PRODUCTO NUEVO',
            'sucursal_id' => $sucursal->id,
        ]);
    }

    /** La edición de Usuario conserva el aislamiento entre inventarios de sucursales distintas. */
    public function test_usuario_no_puede_editar_productos_de_otra_sucursal(): void
    {
        $sucursalUsuario = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $sucursalExterna = Sucursal::create(['nombre' => 'IZAMAL']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursalUsuario->id]);
        $productoExterno = Inventario::create($this->datosProducto('PRODUCTO DE IZAMAL', $sucursalExterna->id));
        $sesion = ['sucursal_id' => $sucursalUsuario->id, 'sucursal_nombre' => $sucursalUsuario->nombre];

        // El controlador responde como registro inexistente para no revelar inventario de otra sucursal.
        $this->actingAs($usuario)->withSession($sesion)->get(route('inventario.edit', $productoExterno))
            ->assertNotFound();
        $this->actingAs($usuario)->withSession($sesion)->put(route('inventario.update', $productoExterno), [
            ...$this->datosProducto('CAMBIO ENTRE SUCURSALES', $sucursalExterna->id),
        ])->assertNotFound();

        $this->assertDatabaseHas('inventario', [
            'id' => $productoExterno->id,
            'nombre' => 'PRODUCTO DE IZAMAL',
            'sucursal_id' => $sucursalExterna->id,
        ]);
    }

    /** El superusuario conserva la administración completa del inventario. */
    public function test_superusuario_conserva_edicion_y_eliminacion_de_productos(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $superusuario = User::factory()->create(['rol' => 'superusuario', 'sucursal_id' => $sucursal->id]);
        $producto = Inventario::create($this->datosProducto('PRODUCTO ADMINISTRABLE', $sucursal->id));
        $sesion = ['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre];

        $this->actingAs($superusuario)->withSession($sesion)->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('Editar')
            ->assertSee('Eliminar');
        $this->actingAs($superusuario)->withSession($sesion)->get(route('inventario.edit', $producto))
            ->assertOk();
        $this->actingAs($superusuario)->withSession($sesion)->delete(route('inventario.destroy', $producto))
            ->assertRedirect(route('inventario.index'));

        $this->assertDatabaseMissing('inventario', ['id' => $producto->id]);
    }

    /** Proporciona los campos relacionados que InventarioController valida al crear o editar. */
    private function datosProducto(string $nombre, int $sucursalId): array
    {
        return [
            'nombre' => $nombre,
            'categoria' => 'ACCESORIO',
            'sucursal_id' => $sucursalId,
            'cantidad_disponible' => 5,
            'stock_minimo' => 1,
            'precio_costo' => 50,
            'precio_venta' => 100,
            'proveedor' => 'PROVEEDOR DE PRUEBA',
            'dispositivo_compatible' => 'CUALQUIER DISPOSITIVO',
            'calidad' => 'NUEVO',
        ];
    }
}
