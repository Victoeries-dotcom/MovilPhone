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

    public function test_usuario_cannot_see_or_use_order_editing(): void
    {
        [$sucursal, $orden] = $this->crearOrden();
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index'))
            ->assertOk()
            // La interfaz no ofrece una acción que el backend rechaza para users.rol = usuario.
            ->assertDontSee(route('ordenes.edit', $orden), false);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.edit', $orden))
            ->assertForbidden();

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.show', $orden))
            ->assertOk()
            ->assertDontSee(route('ordenes.edit', $orden), false);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->put(route('ordenes.update', $orden), [
                'cliente_nombre' => 'CAMBIO NO AUTORIZADO',
                'marca' => $orden->marca,
                'modelo' => $orden->modelo,
                'tipo_dispositivo' => $orden->tipo_dispositivo,
                'estado' => $orden->estado,
                'problema_reportado' => $orden->problema_reportado,
                'estado_fisico' => $orden->estado_fisico,
            ])
            ->assertForbidden();

        $this->assertSame('CLIENTE ORIGINAL', $orden->cliente->fresh()->nombre);
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
