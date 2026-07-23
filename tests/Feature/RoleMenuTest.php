<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Comprueba que una ruta de sucursales no convierta visualmente a Usuario en Super Usuario.
     * Se conecta con layout.blade.php, users.rol y las rutas administrativas protegidas.
     */
    public function test_usuario_conserva_su_menu_limitado_en_el_contexto_de_sucursales(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $usuario = User::factory()->create([
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
        ]);

        /*
         * Esta ruta aislada reproduce una dirección que inicia con /sucursales.
         * Permite validar la vista sin debilitar el middleware real de administración.
         */
        Route::middleware('web')->get('/sucursales/prueba-menu-por-rol', fn () => view('layout'));

        $respuesta = $this
            ->actingAs($usuario)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get('/sucursales/prueba-menu-por-rol');

        $respuesta
            ->assertOk()
            ->assertSee(route('home'), false)
            ->assertSee(route('ordenes.index'), false)
            ->assertSee(route('clientes.index'), false)
            ->assertSee(route('inventario.index'), false)
            ->assertSee(route('caja.index'), false)
            ->assertSee(route('categorias.index'), false)
            ->assertSee(route('ventas.index'), false)
            ->assertDontSee(route('usuarios.index'), false)
            ->assertDontSee(route('sucursales.index'), false)
            ->assertDontSee(route('actividad.index'), false)
            ->assertDontSee(route('reportes.index'), false)
            ->assertDontSee(route('configuracion.edit'), false);
    }

    /**
     * Verifica la seguridad del backend aunque alguien escriba directamente una URL administrativa.
     * Se conecta con RoleMiddleware y mantiene Reportes, Actividad y Configuración para Super Usuario.
     */
    public function test_usuario_no_puede_abrir_rutas_exclusivas_del_superusuario(): void
    {
        $usuario = User::factory()->create(['rol' => 'usuario']);

        $this->actingAs($usuario)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('actividad.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('configuracion.edit'))->assertForbidden();
    }
}
