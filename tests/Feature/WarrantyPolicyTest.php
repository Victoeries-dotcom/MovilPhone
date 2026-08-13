<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_sees_the_complete_warranty_interface_with_default_text(): void
    {
        [$sucursal, $superusuario] = $this->crearSuperusuario();

        $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('configuracion.garantia'))
            ->assertOk()
            ->assertSee('garantia-hero', false)
            ->assertSee('Política de Garantía')
            ->assertSee('Condiciones de garantía')
            ->assertSee('Recomendaciones')
            ->assertSee('Guardar política')
            ->assertSee(config('warranty.default_policy'));
    }

    public function test_warranty_button_opens_the_policy_editor_for_superuser(): void
    {
        [$sucursal, $superusuario] = $this->crearSuperusuario();

        // La acción visible en Órdenes debe abrir la interfaz solicitada, no aplicar el filtro GARANTÍA.
        $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index'))
            ->assertOk()
            ->assertSee('href="'.route('configuracion.garantia').'"', false)
            ->assertSee('>Garantía</span>', false)
            ->assertDontSee('>Política</span>', false);
    }

    public function test_saved_policy_is_shown_on_delivery_ticket_before_folio(): void
    {
        [$sucursal, $superusuario] = $this->crearSuperusuario();
        $politica = 'GARANTÍA PERSONALIZADA PARA LA REPARACIÓN REALIZADA.';

        // El formulario crea o actualiza una sola clave persistente para todos los tickets futuros.
        $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->post(route('configuracion.garantia.guardar'), [
                'politica_garantia' => $politica,
            ])
            ->assertRedirect(route('configuracion.garantia'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('configuraciones', [
            'clave' => 'politica_garantia',
            'valor' => $politica,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'CLIENTE GARANTIA',
            'telefono_principal' => '9995550000',
            'sucursal_habitual_id' => $sucursal->id,
        ]);
        $orden = OrdenServicio::create([
            'numero_os' => 'GAR-2026-0001',
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'tipo_dispositivo' => 'TELÉFONO CELULAR',
            'marca' => 'APPLE',
            'modelo' => 'IPHONE',
            'estado' => 'ENTREGADO',
            'problema_reportado' => 'NO ENCIENDE',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ]);

        $ticket = $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.ticketEntrega', $orden))
            ->assertOk()
            ->assertSee($politica)
            ->assertSee('COPIA CLIENTE')
            ->assertSee('COPIA CAJERA');

        $contenido = $ticket->getContent();

        $this->assertLessThan(strpos($contenido, 'Folio: #'), strpos($contenido, $politica));
        // Dos envolturas con salto de página garantizan dos tickets completos en la vista de impresión.
        $this->assertSame(2, substr_count($contenido, 'class="ticket-print-copy"'));
        // La vista no debe volver a reducir el ticket cuando Chrome ya está configurado en escala 50.
        $ticket->assertDontSee('zoom: 50%', false);
    }

    public function test_regular_user_can_edit_policy_and_future_branches_use_the_same_value(): void
    {
        $sucursalActual = Sucursal::create(['nombre' => 'SUCURSAL ACTUAL']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $sucursalActual->id,
        ]);
        $politica = 'GARANTÍA GLOBAL PARA SUCURSALES ACTUALES Y FUTURAS.';

        // El acceso visible en Órdenes abre el mismo editor que utiliza el Super Usuario.
        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursalActual->id])
            ->get(route('ordenes.index'))
            ->assertOk()
            ->assertSee('href="'.route('configuracion.garantia').'"', false);

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursalActual->id])
            ->get(route('configuracion.garantia'))
            ->assertOk()
            ->assertSee('Guardar política');

        // La clave no contiene sucursal_id: una sola política alimenta los tickets de toda sucursal futura.
        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursalActual->id])
            ->post(route('configuracion.garantia.guardar'), [
                'politica_garantia' => $politica,
            ])
            ->assertRedirect(route('configuracion.garantia'));

        $sucursalFutura = Sucursal::create(['nombre' => 'SUCURSAL FUTURA']);
        $usuarioFuturo = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $sucursalFutura->id,
        ]);

        $this->actingAs($usuarioFuturo)
            ->withSession(['sucursal_id' => $sucursalFutura->id])
            ->get(route('configuracion.garantia'))
            ->assertOk()
            ->assertSee($politica);
    }

    /**
     * Crea el contexto administrativo mínimo conectado con una sucursal activa.
     *
     * @return array{Sucursal, User}
     */
    private function crearSuperusuario(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL GARANTIA']);
        $superusuario = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        return [$sucursal, $superusuario];
    }
}
