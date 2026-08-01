<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServicioTechnicianEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_only_lists_technicians_from_the_orders_branch(): void
    {
        [$usuario, $orden, $tecnicoLocal, $tecnicoExterno, $vendedorLocal] = $this->crearEscenario();

        // La respuesta HTML representa las opciones que el usuario puede elegir en el formulario.
        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $orden->sucursal_id])
            ->get(route('ordenes.edit', $orden))
            ->assertOk()
            ->assertSee($tecnicoLocal->name)
            ->assertDontSee($tecnicoExterno->name)
            ->assertDontSee($vendedorLocal->name);
    }

    public function test_update_rejects_a_technician_from_another_branch(): void
    {
        [$usuario, $orden, , $tecnicoExterno] = $this->crearEscenario();

        // Todos los campos son validos salvo el tecnico externo, que debe rechazarse en el servidor.
        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $orden->sucursal_id])
            ->from(route('ordenes.edit', $orden))
            ->put(route('ordenes.update', $orden), [
                'cliente_nombre' => $orden->cliente->nombre,
                'cliente_telefono' => $orden->cliente->telefono_principal,
                'marca' => $orden->marca,
                'modelo' => $orden->modelo,
                'tipo_dispositivo' => $orden->tipo_dispositivo,
                'tecnico_id' => $tecnicoExterno->id,
                'problema_reportado' => $orden->problema_reportado,
                'estado_fisico' => $orden->estado_fisico,
            ])
            ->assertRedirect(route('ordenes.edit', $orden))
            ->assertSessionHasErrors('tecnico_id');

        $this->assertNull($orden->fresh()->tecnico_id);
    }

    /**
     * Construye roles y sucursales distintos para comprobar las exclusiones del selector.
     *
     * @return array{User, OrdenServicio, User, User, User}
     */
    private function crearEscenario(): array
    {
        $sucursalOrden = Sucursal::create(['nombre' => 'SUCURSAL ORDEN']);
        $otraSucursal = Sucursal::create(['nombre' => 'SUCURSAL EXTERNA']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursalOrden->id]);
        $tecnicoLocal = User::factory()->create([
            'name' => 'TECNICO LOCAL VISIBLE',
            'rol' => 'tecnico',
            'sucursal_id' => $sucursalOrden->id,
        ]);
        $tecnicoExterno = User::factory()->create([
            'name' => 'TECNICO EXTERNO OCULTO',
            'rol' => 'tecnico',
            'sucursal_id' => $otraSucursal->id,
        ]);
        $vendedorLocal = User::factory()->create([
            'name' => 'VENDEDOR LOCAL OCULTO',
            'rol' => 'vendedor',
            'sucursal_id' => $sucursalOrden->id,
        ]);
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE PRUEBA',
            'telefono_principal' => '9990000000',
            'sucursal_habitual_id' => $sucursalOrden->id,
        ]);
        $orden = OrdenServicio::create([
            'numero_os' => 'SUC-2026-0001',
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursalOrden->id,
            'tipo_dispositivo' => 'TELEFONO',
            'marca' => 'MARCA',
            'modelo' => 'MODELO',
            'problema_reportado' => 'NO ENCIENDE',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ]);

        return [$usuario, $orden, $tecnicoLocal, $tecnicoExterno, $vendedorLocal];
    }
}
