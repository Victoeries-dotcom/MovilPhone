<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServicioOptionalPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_orders_can_create_independent_customers_without_phone_numbers(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // Registra dos clientes sin teléfono para comprobar que no comparten una identidad vacía.
        foreach (['CLIENTE SIN TELÉFONO UNO', 'CLIENTE SIN TELÉFONO DOS'] as $nombre) {
            $this->actingAs($usuario)
                ->withSession($sesion)
                ->post(route('ordenes.store'), $this->datosOrden($nombre, $sucursal->id))
                ->assertRedirect(route('ordenes.index'))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('clientes', 2);
        $this->assertDatabaseCount('ordenes_servicio', 2);
        $this->assertDatabaseHas('clientes', [
            'nombre' => 'CLIENTE SIN TELÉFONO UNO',
            'telefono_principal' => null,
            'telefono_normalizado' => null,
        ]);
    }

    public function test_optional_phone_still_requires_exactly_ten_digits_when_provided(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // El campo puede omitirse, pero un número parcial debe rechazarse en el servidor.
        $response = $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('ordenes.store'), array_merge(
                $this->datosOrden('CLIENTE CON NÚMERO INVÁLIDO', $sucursal->id),
                ['cliente_telefono' => '999123']
            ));

        $response->assertSessionHasErrors('cliente_telefono');
        $this->assertDatabaseCount('ordenes_servicio', 0);
    }

    public function test_order_without_phone_can_be_edited_without_forcing_a_contact_number(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // Crea primero la orden mediante el flujo público para obtener un cliente sin teléfono relacionado.
        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('ordenes.store'), $this->datosOrden('CLIENTE EDITABLE SIN TELÉFONO', $sucursal->id))
            ->assertRedirect(route('ordenes.index'));

        $orden = OrdenServicio::with('cliente')->firstOrFail();

        // Edita la misma OS sin agregar teléfono y comprueba que la regla opcional se conserva.
        $response = $this->actingAs($usuario)
            ->withSession($sesion)
            ->put(route('ordenes.update', $orden), [
                'cliente_nombre' => 'CLIENTE EDITADO SIN TELÉFONO',
                'tipo_dispositivo' => $orden->tipo_dispositivo,
                'marca' => $orden->marca,
                'modelo' => $orden->modelo,
                'estado' => $orden->estado,
                'problema_reportado' => $orden->problema_reportado,
                'estado_fisico' => $orden->estado_fisico,
            ]);

        $response->assertRedirect(route('ordenes.show', $orden))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clientes', [
            'id' => $orden->cliente_id,
            'nombre' => 'CLIENTE EDITADO SIN TELÉFONO',
            'telefono_principal' => null,
            'telefono_normalizado' => null,
        ]);
    }

    public function test_new_order_saves_the_device_diagnostic_amount(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // Comprueba que el campo numérico del asistente llegue a ordenes_servicio.cobro_diagnostico.
        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('ordenes.store'), array_merge(
                $this->datosOrden('CLIENTE CON DIAGNOSTICO', $sucursal->id),
                ['cobro_diagnostico' => 200.50]
            ))
            ->assertRedirect(route('ordenes.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ordenes_servicio', [
            'cobro_diagnostico' => 200.50,
        ]);
    }

    public function test_new_order_rejects_a_negative_device_diagnostic_amount(): void
    {
        [$usuario, $sucursal, $sesion] = $this->contextoDeSucursal();

        // La validación del servidor impide registrar importes negativos aunque se manipule el formulario.
        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('ordenes.store'), array_merge(
                $this->datosOrden('CLIENTE CON DIAGNOSTICO INVALIDO', $sucursal->id),
                ['cobro_diagnostico' => -1]
            ))
            ->assertSessionHasErrors('cobro_diagnostico');

        $this->assertDatabaseCount('ordenes_servicio', 0);
    }

    /**
     * Crea el usuario y la sucursal que exige la protección de órdenes.
     *
     * @return array{User, Sucursal, array<string, int|string>}
     */
    private function contextoDeSucursal(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL TELÉFONO OPCIONAL']);
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
     * Representa los campos mínimos del asistente y omite intencionalmente el teléfono.
     *
     * @return array<string, int|string>
     */
    private function datosOrden(string $nombre, int $sucursalId): array
    {
        return [
            'cliente_nombre' => $nombre,
            'sucursal_id' => $sucursalId,
            'tipo_dispositivo' => 'TELÉFONO',
            'marca' => 'SAMSUNG',
            'modelo' => 'GALAXY A15',
            'problema_reportado' => 'NO ENCIENDE',
            'estado_fisico' => 'BUEN ESTADO',
        ];
    }
}
