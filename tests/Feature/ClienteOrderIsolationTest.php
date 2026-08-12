<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteOrderIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_with_the_same_customer_data_keep_independent_customer_records(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // Dos órdenes pueden repetir nombre y teléfono sin fusionar sus clientes ni sus historiales.
        foreach (['GALAXY A15', 'MOTO G23'] as $modelo) {
            $this->actingAs($usuario)
                ->withSession($sesion)
                ->post(route('ordenes.store'), $this->datosOrden($sucursal->id, $modelo))
                ->assertRedirect(route('ordenes.index'))
                ->assertSessionHasNoErrors();
        }

        $ordenes = OrdenServicio::orderBy('id')->get();

        $this->assertCount(2, $ordenes);
        $this->assertNotSame($ordenes[0]->cliente_id, $ordenes[1]->cliente_id);
        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_editing_one_legacy_shared_order_separates_its_customer_from_other_orders(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // Representa datos antiguos donde dos órdenes todavía apuntan al mismo clientes.id.
        $clienteCompartido = Cliente::create([
            'nombre' => 'EDGAR RIVERO',
            'telefono_principal' => '9911250446',
            'sucursal_habitual_id' => $sucursal->id,
        ]);
        $primeraOrden = $this->crearOrden($clienteCompartido, $sucursal, 'GALAXY A15');
        $segundaOrden = $this->crearOrden($clienteCompartido, $sucursal, 'MOTO G23');

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->put(route('ordenes.update', $primeraOrden), array_merge(
                $this->datosEdicion($primeraOrden),
                ['cliente_nombre' => 'EDGAR EDITADO']
            ))
            ->assertRedirect(route('ordenes.show', $primeraOrden))
            ->assertSessionHasNoErrors();

        $primeraOrden->refresh();
        $segundaOrden->refresh();

        $this->assertNotSame($primeraOrden->cliente_id, $segundaOrden->cliente_id);
        $this->assertSame('EDGAR EDITADO', $primeraOrden->cliente()->value('nombre'));
        $this->assertSame('EDGAR RIVERO', $segundaOrden->cliente()->value('nombre'));
    }

    public function test_editing_a_customer_by_id_does_not_modify_another_customer_with_the_same_name(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // Los registros comparten nombre, pero cada formulario se dirige a un clientes.id diferente.
        $clienteEditado = Cliente::create([
            'nombre' => 'EDGAR RIVERO',
            'telefono_principal' => '9911111111',
            'sucursal_habitual_id' => $sucursal->id,
        ]);
        $clienteIntacto = Cliente::create([
            'nombre' => 'EDGAR RIVERO',
            'telefono_principal' => '9922222222',
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        $this->actingAs($usuario)
            ->withSession($sesion)
            ->put(route('clientes.update', $clienteEditado), [
                'nombre' => 'EDGAR ACTUALIZADO',
                'telefono_principal' => '9933333333',
            ])
            ->assertRedirect(route('clientes.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes', [
            'id' => $clienteEditado->id,
            'nombre' => 'EDGAR ACTUALIZADO',
            'telefono_principal' => '9933333333',
        ]);
        $this->assertDatabaseHas('clientes', [
            'id' => $clienteIntacto->id,
            'nombre' => 'EDGAR RIVERO',
            'telefono_principal' => '9922222222',
        ]);
    }

    /**
     * Prepara una cuenta de taller y la sesión que protege todas las rutas de la sucursal.
     *
     * @return array{User, Sucursal, array<string, int|string>}
     */
    private function contextoDeSucursal(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL AISLAMIENTO']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
        ]);

        return [$usuario, $sucursal, [
            'sucursal_id' => $sucursal->id,
            'sucursal_nombre' => $sucursal->nombre,
        ]];
    }

    /**
     * Contiene los campos mínimos del asistente para crear órdenes equivalentes con distinto equipo.
     *
     * @return array<string, int|string>
     */
    private function datosOrden(int $sucursalId, string $modelo): array
    {
        return [
            'cliente_nombre' => 'EDGAR RIVERO',
            'cliente_telefono' => '9911250446',
            'sucursal_id' => $sucursalId,
            'tipo_dispositivo' => 'TELÉFONO',
            'marca' => 'SAMSUNG',
            'modelo' => $modelo,
            'problema_reportado' => 'NO ENCIENDE',
            'estado_fisico' => 'BUEN ESTADO',
        ];
    }

    /**
     * Reproduce una relación histórica compartida para comprobar la separación al editar.
     */
    private function crearOrden(Cliente $cliente, Sucursal $sucursal, string $modelo): OrdenServicio
    {
        return OrdenServicio::create([
            'numero_os' => 'AIS-2026-'.str_pad((string) (OrdenServicio::count() + 1), 4, '0', STR_PAD_LEFT),
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'tipo_dispositivo' => 'TELÉFONO',
            'marca' => 'SAMSUNG',
            'modelo' => $modelo,
            'estado' => 'RECIBIDO',
            'problema_reportado' => 'NO ENCIENDE',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUEN ESTADO',
        ]);
    }

    /**
     * Conserva los datos obligatorios de la OS y permite cambiar únicamente el cliente solicitado.
     *
     * @return array<string, int|string|null>
     */
    private function datosEdicion(OrdenServicio $orden): array
    {
        return [
            'cliente_nombre' => $orden->cliente->nombre,
            'cliente_telefono' => $orden->cliente->telefono_principal,
            'tipo_dispositivo' => $orden->tipo_dispositivo,
            'marca' => $orden->marca,
            'modelo' => $orden->modelo,
            'estado' => $orden->estado,
            'problema_reportado' => $orden->problema_reportado,
            'estado_fisico' => $orden->estado_fisico,
        ];
    }
}
