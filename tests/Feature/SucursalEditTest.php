<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SucursalEditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Comprueba el flujo completo conectado entre tabla, formulario,
     * controlador, base de datos y sesión de la sucursal activa.
     */
    public function test_superusuario_puede_editar_una_sucursal_con_datos_precargados(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'IZAMAL',
            'ubicacion' => 'CALLE ANTIGUA',
            'ubicacion_url' => 'https://maps.example.com/antigua',
            'nombre_encargado' => 'ENCARGADO ANTERIOR',
            'telefono_encargado' => '9990000000',
            'horario' => '9:00 A.M - 7:00 P.M',
        ]);

        $superusuario = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        // La tabla debe mostrar el enlace que abre la edición de esta sucursal.
        $this->actingAs($superusuario)
            ->withSession([
                'sucursal_id' => $sucursal->id,
                'sucursal_nombre' => $sucursal->nombre,
            ])
            ->get(route('sucursales.index'))
            ->assertOk()
            ->assertSee(route('sucursales.edit', $sucursal), false);

        // El formulario debe recuperar los datos actuales antes de modificarlos.
        $this->actingAs($superusuario)
            ->get(route('sucursales.edit', $sucursal))
            ->assertOk()
            ->assertSee('CALLE ANTIGUA')
            ->assertSee('9990000000')
            ->assertSee(route('sucursales.update', $sucursal), false);

        // La actualización persiste los nuevos datos y refresca la sucursal activa.
        $this->actingAs($superusuario)
            ->withSession([
                'sucursal_id' => $sucursal->id,
                'sucursal_nombre' => $sucursal->nombre,
            ])
            ->put(route('sucursales.update', $sucursal), [
                'nombre' => 'Buctzotz centro',
                'ubicacion' => 'calle 17 entre 20 y 22',
                'ubicacion_url' => 'https://maps.example.com/NuevaRuta',
                'nombre_encargado' => 'persona nueva',
                'telefono_encargado' => '9911098036',
                'horario' => '9:00 a.m - 9:00 p.m',
            ])
            ->assertRedirect(route('sucursales.index', ['sucursal_id' => $sucursal->id]))
            ->assertSessionHas('sucursal_nombre', 'BUCTZOTZ CENTRO');

        $this->assertDatabaseHas('sucursales', [
            'id' => $sucursal->id,
            'nombre' => 'BUCTZOTZ CENTRO',
            'ubicacion' => 'CALLE 17 ENTRE 20 Y 22',
            'ubicacion_url' => 'https://maps.example.com/NuevaRuta',
            'nombre_encargado' => 'PERSONA NUEVA',
            'telefono_encargado' => '9911098036',
            'horario' => '9:00 A.M - 9:00 P.M',
        ]);
    }
}
