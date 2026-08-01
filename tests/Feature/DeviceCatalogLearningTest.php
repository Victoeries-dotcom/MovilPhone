<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceCatalogLearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_device_brand_and_model_are_available_in_the_next_order(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'SUCURSAL CATALOGO']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
        ]);
        $sesion = [
            'sucursal_id' => $sucursal->id,
            'sucursal_nombre' => $sucursal->nombre,
        ];

        // Simula los tres textos capturados mediante la opción "Otro" del asistente.
        $this->actingAs($usuario)
            ->withSession($sesion)
            ->post(route('ordenes.store'), [
                'cliente_nombre' => 'CLIENTE CATALOGO',
                'cliente_telefono' => '9991234567',
                'sucursal_id' => $sucursal->id,
                'tipo_dispositivo' => 'CONSOLA PORTATIL ESPECIAL',
                'marca' => 'MARCA MANUAL ESPECIAL',
                'modelo' => 'MODELO MANUAL 2026',
                'problema_reportado' => 'NO ENCIENDE',
                'estado_fisico' => 'BUENO',
            ])
            ->assertRedirect(route('ordenes.index'));

        $this->assertDatabaseHas('ordenes_servicio', [
            'tipo_dispositivo' => 'CONSOLA PORTATIL ESPECIAL',
            'marca' => 'MARCA MANUAL ESPECIAL',
            'modelo' => 'MODELO MANUAL 2026',
        ]);
        $this->assertDatabaseHas('device_catalog_entries', [
            'device_type' => 'CONSOLA PORTATIL ESPECIAL',
            'brand' => 'MARCA MANUAL ESPECIAL',
            'model' => 'MODELO MANUAL 2026',
        ]);

        // La siguiente apertura recibe la combinación aprendida dentro del catálogo JavaScript.
        $this->actingAs($usuario)
            ->withSession($sesion)
            ->get(route('ordenes.create'))
            ->assertOk()
            ->assertSee('CONSOLA PORTATIL ESPECIAL')
            ->assertSee('MARCA MANUAL ESPECIAL')
            ->assertSee('MODELO MANUAL 2026');
    }
}
