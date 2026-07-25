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
     * Se conecta con MovimientoCajaController, sus indicadores y movimientos_caja.
     */
    public function test_caja_sin_sucursal_muestra_indicadores_en_cero_y_tabla_vacia(): void
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
            ->assertViewHas('stats', [
                'ingresos' => 0,
                'egresos' => 0,
                'balance' => 0,
                'anticipos' => 0,
                'movimientos' => 0,
            ])
            ->assertViewHas('movimientos', fn ($movimientos) => $movimientos->isEmpty())
            ->assertDontSee('MOVIMIENTO EXCLUSIVO BUCTZOTZ')
            ->assertDontSee('$46,865.00');
    }
}
