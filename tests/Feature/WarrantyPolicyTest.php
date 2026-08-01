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

        $contenido = $this->actingAs($superusuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.ticketEntrega', $orden))
            ->assertOk()
            ->assertSee($politica)
            ->getContent();

        $this->assertLessThan(strpos($contenido, 'Folio: #'), strpos($contenido, $politica));
    }

    public function test_regular_user_cannot_edit_the_global_warranty_policy(): void
    {
        $usuario = User::factory()->create(['rol' => 'usuario']);

        $this->actingAs($usuario)
            ->get(route('configuracion.garantia'))
            ->assertForbidden();
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
