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
            'correo_contacto' => ' contacto@movilphone.com ',
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
        $this->assertSame('contacto@movilphone.com', $tecnico->correo_contacto);
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

    /** Verifica que alta, contacto y credencial de acceso se almacenen en campos independientes. */
    public function test_admin_creates_user_with_separate_contact_and_login_emails(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'IZAMAL']);
        $admin = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        // La vista debe explicar claramente cuál correo autentica al usuario.
        $this->actingAs($admin)
            ->get(route('usuarios.create'))
            ->assertOk()
            ->assertSee('Correo electrónico para ingresar como usuario');

        // El formulario envía contacto y acceso a columnas diferentes de users.
        $respuesta = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'USUARIO IZAMAL',
            'telefono' => '9991234567',
            'correo_contacto' => ' CONTACTO@EJEMPLO.COM ',
            'email' => ' ACCESO@EJEMPLO.COM ',
            'rol' => 'usuario',
            'sucursal_id' => $sucursal->id,
            'password' => 'ClaveSegura2026',
            'password_confirmation' => 'ClaveSegura2026',
        ]);

        $respuesta->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'USUARIO IZAMAL',
            'correo_contacto' => 'contacto@ejemplo.com',
            'email' => 'acceso@ejemplo.com',
        ]);

        // Solo el correo de acceso autentica; el correo de contacto permanece informativo.
        auth()->logout();
        $this->post('/login', [
            'email' => 'acceso@ejemplo.com',
            'password' => 'ClaveSegura2026',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }
}
