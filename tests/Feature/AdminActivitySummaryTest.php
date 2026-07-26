<?php

namespace Tests\Feature;

use App\Models\AdminActivity;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivitySummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica el resumen por usuario sin mezclar datos entre sucursales.
     * Se conecta con AdminActivityController, admin_activities, users y sucursales.
     */
    public function test_resumen_agrupa_acciones_del_usuario_en_la_sucursal_activa(): void
    {
        $buctzotz = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $izamal = Sucursal::create(['nombre' => 'IZAMAL']);
        $admin = User::factory()->create(['rol' => 'superusuario', 'sucursal_id' => null]);
        $usuario = User::factory()->create([
            'name' => 'USUARIO BUCTZOTZ',
            'rol' => 'usuario',
            'sucursal_id' => $buctzotz->id,
        ]);

        foreach (['CREAR', 'CREAR', 'EDITAR', 'ELIMINAR', 'CAMBIAR ESTADO'] as $accion) {
            AdminActivity::create([
                'modulo' => 'VENTAS',
                'accion' => $accion,
                'descripcion' => "Actividad {$accion}",
                'sucursal_id' => $buctzotz->id,
                'user_id' => $usuario->id,
            ]);
        }

        AdminActivity::create([
            'modulo' => 'VENTAS',
            'accion' => 'CREAR',
            'descripcion' => 'ACTIVIDAD EXCLUSIVA IZAMAL',
            'sucursal_id' => $izamal->id,
            'user_id' => $usuario->id,
        ]);

        $respuesta = $this->actingAs($admin)
            ->withSession(['sucursal_id' => $buctzotz->id])
            ->get(route('actividad.index'));

        $respuesta->assertOk()
            ->assertSee('Usuarios con actividad registrada')
            ->assertSee('USUARIO BUCTZOTZ')
            ->assertDontSee('ACTIVIDAD EXCLUSIVA IZAMAL')
            ->assertViewHas('totalesActividad', [
                'total' => 5,
                'agregados' => 2,
                'editados' => 1,
                'eliminados' => 1,
                'otras' => 1,
            ])
            ->assertViewHas('resumenUsuarios', function ($resumen) use ($usuario, $buctzotz) {
                $fila = $resumen->first();

                return $resumen->count() === 1
                    && (int) $fila->user_id === $usuario->id
                    && (int) $fila->sucursal_id === $buctzotz->id
                    && (int) $fila->total_registros === 5
                    && (int) $fila->agregados === 2
                    && (int) $fila->editados === 1
                    && (int) $fila->eliminados === 1
                    && (int) $fila->otras_acciones === 1;
            });
    }
}
