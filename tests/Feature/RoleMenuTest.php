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
            // El atributo conecta el rol con el modo claro fijo de la experiencia Usuario.
            ->assertSee('data-user-role="usuario"', false)
            ->assertDontSee('id="themeToggle"', false)
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
     * Comprueba que Super Usuario conserve el control de tema claro/oscuro.
     * Se conecta con layout.blade.php y movilphone-ui.js sin exponerlo al rol Usuario.
     */
    public function test_superusuario_conserva_el_selector_de_tema(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $superusuario = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        Route::middleware('web')->get('/prueba-tema-superusuario', fn () => view('layout'));

        $this
            ->actingAs($superusuario)
            ->withSession([
                'sucursal_id' => $sucursal->id,
                'sucursal_nombre' => $sucursal->nombre,
            ])
            ->get('/prueba-tema-superusuario')
            ->assertOk()
            ->assertSee('data-user-role="superusuario"', false)
            ->assertSee('id="themeToggle"', false);
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

    /**
     * Comprueba que Técnico solo vea Órdenes, Clientes y Caja, que son sus rutas autorizadas.
     * Se conecta con el menú lateral y evita accesos visuales que terminarían en un error 403.
     */
    public function test_tecnico_ve_unicamente_sus_modulos_autorizados(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $tecnico = User::factory()->create([
            'rol' => 'tecnico',
            'sucursal_id' => $sucursal->id,
        ]);

        Route::middleware('web')->get('/sucursales/prueba-menu-tecnico', fn () => view('layout'));

        $respuesta = $this
            ->actingAs($tecnico)
            ->withSession(['sucursal_id' => $sucursal->id])
            ->get('/sucursales/prueba-menu-tecnico');

        $respuesta
            ->assertOk()
            ->assertSee(route('ordenes.index'), false)
            ->assertSee(route('clientes.index'), false)
            ->assertSee(route('caja.index'), false)
            ->assertDontSee(route('inventario.index'), false)
            ->assertDontSee(route('categorias.index'), false)
            ->assertDontSee(route('ventas.index'), false)
            ->assertDontSee(route('reportes.index'), false)
            ->assertDontSee(route('actividad.index'), false)
            ->assertDontSee(route('configuracion.edit'), false);
    }
}
