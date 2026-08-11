<?php

namespace Tests\Feature;

use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchlessAdministrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que Usuarios muestre la cuenta administrativa global sin una sede activa.
     * Se conecta con UsuarioController, users.rol y la credencial users.email.
     */
    public function test_usuarios_sin_sucursal_muestra_solo_cuentas_administrativas(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $admin = User::factory()->create([
            'name' => 'ADMIN GLOBAL',
            'email' => 'admin-global@movilphone.com',
            'rol' => 'superusuario',
            'sucursal_id' => null,
        ]);
        User::factory()->create([
            'name' => 'TECNICO BUCTZOTZ',
            'rol' => 'tecnico',
            'sucursal_id' => $sucursal->id,
        ]);

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('ADMIN GLOBAL')
            ->assertSee('admin-global@movilphone.com')
            ->assertSee('Las contraseñas están cifradas')
            ->assertDontSee('TECNICO BUCTZOTZ');
    }

    /**
     * Verifica que Caja no mezcle información cuando aún no se eligió una sucursal.
     * Se conecta con MovimientoCajaController y la tabla movimientos_caja sin duplicar el resumen de Reportes.
     */
    public function test_caja_sin_sucursal_oculta_resumen_financiero_y_mantiene_tabla_vacia(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);
        $admin = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => null,
        ]);
        MovimientoCaja::create([
            'sucursal_id' => $sucursal->id,
            'tipo' => 'INGRESO',
            'categoria' => 'VENTA',
            'monto' => 46865,
            'anticipo' => 0,
            'metodo_pago' => 'efectivo',
            'descripcion' => 'MOVIMIENTO EXCLUSIVO BUCTZOTZ',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('caja.index'))
            ->assertOk()
            // El resumen financiero ahora pertenece a Reportes y no debe duplicarse en Caja.
            ->assertDontSee('Resumen de Caja')
            ->assertDontSee('Total ingresos')
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->isEmpty())
            ->assertDontSee('MOVIMIENTO EXCLUSIVO BUCTZOTZ')
            ->assertDontSee('$46,865.00');
    }
}
