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

    /**
     * El rol usuario puede consultar y agregar, pero no modificar ni eliminar productos existentes.
     */
    public function test_usuario_solo_puede_consultar_y_agregar_productos(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);
        $producto = Inventario::create($this->datosProducto('PRODUCTO PROTEGIDO', $sucursal->id));
        $sesion = ['sucursal_id' => $sucursal->id, 'sucursal_nombre' => $sucursal->nombre];

        $this->actingAs($usuario)->withSession($sesion)->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('Agregar producto')
            ->assertSee(route('inventario.create'), false)
            // Busca los controles reales para no confundirlos con textos del JavaScript global.
            ->assertDontSee('inventory-edit-button', false)
            ->assertDontSee('inventory-delete-button', false);

        // Las rutas directas también deben rechazar intentos manipulados fuera de la interfaz.
        $this->actingAs($usuario)->withSession($sesion)->get(route('inventario.edit', $producto))
            ->assertForbidden();
        $this->actingAs($usuario)->withSession($sesion)->put(route('inventario.update', $producto), [
            ...$this->datosProducto('PRODUCTO ALTERADO', $sucursal->id),
        ])->assertForbidden();
        $this->actingAs($usuario)->withSession($sesion)->delete(route('inventario.destroy', $producto))
            ->assertForbidden();

        $this->assertDatabaseHas('inventario', [
            'id' => $producto->id,
            'nombre' => 'PRODUCTO PROTEGIDO',
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
