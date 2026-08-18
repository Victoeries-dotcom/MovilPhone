<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServicioEditAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** Usuario puede abrir y guardar la edición de una orden perteneciente a su sucursal. */
    public function test_usuario_puede_ver_y_usar_la_edicion_de_ordenes(): void
    {
        [$sucursal, $orden] = $this->crearOrden();
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index'))
            ->assertOk()
            // La interfaz ofrece la misma acción autorizada por el middleware de la ruta.
            ->assertSee(route('ordenes.edit', $orden), false);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.edit', $orden))
            ->assertOk();

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.show', $orden))
            ->assertOk()
            ->assertSee(route('ordenes.edit', $orden), false);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->put(route('ordenes.update', $orden), [
                'cliente_nombre' => 'CLIENTE ACTUALIZADO',
                'cliente_telefono' => $orden->cliente->telefono_principal,
                'marca' => $orden->marca,
                'modelo' => $orden->modelo,
                'tipo_dispositivo' => $orden->tipo_dispositivo,
                'estado' => $orden->estado,
                'problema_reportado' => $orden->problema_reportado,
                'estado_fisico' => $orden->estado_fisico,
            ])
            ->assertRedirect(route('ordenes.show', $orden));

        $this->assertSame('CLIENTE ACTUALIZADO', $orden->fresh()->cliente->nombre);
    }

    /** Usuario no puede abrir ni actualizar una orden perteneciente a otra sucursal. */
    public function test_usuario_no_puede_editar_ordenes_de_otra_sucursal(): void
    {
        [$sucursalExterna, $ordenExterna] = $this->crearOrden('ORDEN-EXTERNA');
        $sucursalUsuario = Sucursal::create(['nombre' => 'SUCURSAL DEL USUARIO']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursalUsuario->id]);
        $sesion = ['sucursal_id' => $sucursalUsuario->id];

        // El controlador valida la sucursal activa incluso cuando se escribe manualmente la URL.
        $this->actingAs($usuario)->withSession($sesion)->get(route('ordenes.edit', $ordenExterna))
            ->assertForbidden();
        $this->actingAs($usuario)->withSession($sesion)->put(route('ordenes.update', $ordenExterna), [
            'cliente_nombre' => 'CLIENTE ENTRE SUCURSALES',
            'cliente_telefono' => $ordenExterna->cliente->telefono_principal,
            'marca' => $ordenExterna->marca,
            'modelo' => $ordenExterna->modelo,
            'tipo_dispositivo' => $ordenExterna->tipo_dispositivo,
            'estado' => $ordenExterna->estado,
            'problema_reportado' => $ordenExterna->problema_reportado,
            'estado_fisico' => $ordenExterna->estado_fisico,
        ])->assertForbidden();

        $this->assertSame('CLIENTE ORIGINAL', $ordenExterna->fresh()->cliente->nombre);
        $this->assertNotSame($sucursalUsuario->id, $sucursalExterna->id);
    }

    public function test_superuser_and_technician_keep_access_to_order_editing(): void
    {
        foreach (['superusuario', 'tecnico'] as $rol) {
            [$sucursal, $orden] = $this->crearOrden('ORDEN-'.$rol);
            $empleado = User::factory()->create(['rol' => $rol, 'sucursal_id' => $sucursal->id]);

            $this->actingAs($empleado)
                ->withSession(['sucursal_id' => $sucursal->id])
                ->get(route('ordenes.index'))
                ->assertOk()
                ->assertSee(route('ordenes.edit', $orden), false);

            $this->actingAs($empleado)
                ->withSession(['sucursal_id' => $sucursal->id])
                ->get(route('ordenes.edit', $orden))
                ->assertOk();

            $this->actingAs($empleado)
                ->withSession(['sucursal_id' => $sucursal->id])
                ->get(route('ordenes.show', $orden))
                ->assertOk()
                ->assertSee(route('ordenes.edit', $orden), false);
        }
    }

    /**
     * Crea la relación mínima cliente-orden-sucursal usada para comprobar el permiso real.
     *
     * @return array{Sucursal, OrdenServicio}
     */
    private function crearOrden(string $folio = 'ORDEN-EDICION'): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL '.$folio]);
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE ORIGINAL',
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
