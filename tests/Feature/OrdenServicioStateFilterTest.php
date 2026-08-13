<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdenServicio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenServicioStateFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_selector_offers_waiting_and_delivered_orders(): void
    {
        [$usuario, $sucursal] = $this->crearUsuarioYSucursal();

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index'))
            ->assertOk()
            // Cada etiqueta conserva como value el estado exacto almacenado en ordenes_servicio.estado.
            ->assertSeeInOrder(['value="RECIBIDO"', '>En espera</option>'], false)
            ->assertSeeInOrder(['value="ENTREGADO"', '>Entregado</option>'], false);
    }

    public function test_filters_only_waiting_orders(): void
    {
        [$usuario, $sucursal] = $this->crearUsuarioYSucursal();
        $this->crearOrden($sucursal, 'OS-EN-ESPERA', 'RECIBIDO');
        $this->crearOrden($sucursal, 'OS-ENTREGADA', 'ENTREGADO');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index', ['estado' => 'RECIBIDO']))
            ->assertOk()
            ->assertSee('OS-EN-ESPERA')
            ->assertDontSee('OS-ENTREGADA');
    }

    public function test_filters_only_delivered_orders(): void
    {
        [$usuario, $sucursal] = $this->crearUsuarioYSucursal();
        $this->crearOrden($sucursal, 'OS-EN-ESPERA', 'RECIBIDO');
        $this->crearOrden($sucursal, 'OS-ENTREGADA', 'ENTREGADO');

        $this->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get(route('ordenes.index', ['estado' => 'ENTREGADO']))
            ->assertOk()
            ->assertSee('OS-ENTREGADA')
            ->assertDontSee('OS-EN-ESPERA');
    }

    private function crearUsuarioYSucursal(): array
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL FILTRO']);
        $usuario = User::factory()->create(['rol' => 'usuario', 'sucursal_id' => $sucursal->id]);

        return [$usuario, $sucursal];
    }

    private function crearOrden(Sucursal $sucursal, string $folio, string $estado): OrdenServicio
    {
        $cliente = Cliente::create([
            'nombre' => 'CLIENTE '.$folio,
            'telefono_principal' => '999'.str_pad((string) Cliente::count(), 7, '0', STR_PAD_LEFT),
            'sucursal_habitual_id' => $sucursal->id,
        ]);

        return OrdenServicio::create([
            'numero_os' => $folio,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'estado' => $estado,
            'tipo_dispositivo' => 'TELÉFONO',
            'marca' => 'MARCA',
            'modelo' => 'MODELO',
            'problema_reportado' => 'PRUEBA DE FILTRO',
            'accesorios_entregados' => 'NINGUNO',
            'estado_fisico' => 'BUENO',
        ]);
    }
}
