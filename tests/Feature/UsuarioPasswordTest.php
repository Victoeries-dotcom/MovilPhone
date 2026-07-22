<?php

namespace Tests\Feature;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** Verifica el recorrido real: edición administrativa, hash guardado y acceso posterior por login. */
    public function test_admin_can_convert_technician_and_assign_a_working_password(): void
    {
        // La sucursal conecta al administrador y al técnico con el mismo contexto operativo.
        $sucursal = Sucursal::create(['nombre' => 'BUCTZOTZ']);

        $admin = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        $tecnico = User::factory()->create([
            'name' => 'USUARIO DE PRUEBA',
            'email' => 'amigo@movilphone.com',
            'rol' => 'tecnico',
            'sucursal_id' => $sucursal->id,
        ]);

        // Envía los mismos campos del formulario Editar Usuario a UsuarioController::update.
        $respuesta = $this->actingAs($admin)->put(route('usuarios.update', $tecnico), [
            'name' => $tecnico->name,
            'telefono' => '9911064338',
            'email' => ' AMIGO@MOVILPHONE.COM ',
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
            'password' => 'ClaveSegura2026',
            'password_confirmation' => 'ClaveSegura2026',
        ]);

        $respuesta->assertRedirect(route('usuarios.index'));
        $respuesta->assertSessionHas('success', 'Usuario y contraseña actualizados correctamente.');

        $tecnico->refresh();
        $this->assertSame('usuario', $tecnico->rol);
        $this->assertSame('amigo@movilphone.com', $tecnico->email);
        $this->assertTrue(Hash::check('ClaveSegura2026', $tecnico->password));

        // Comprueba que la contraseña recién guardada autentica al mismo usuario en LoginRequest.
        auth()->logout();
        $login = $this->post('/login', [
            'email' => ' AMIGO@MOVILPHONE.COM ',
            'password' => 'ClaveSegura2026',
        ]);

        $login->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($tecnico);
    }
}
