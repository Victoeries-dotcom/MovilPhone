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

    /** Confirma que la interfaz y users.rol acepten únicamente los tres perfiles vigentes. */
    public function test_admin_only_sees_and_can_assign_current_roles(): void
    {
        $sucursal = Sucursal::create(['nombre' => 'MOTUL']);
        $admin = User::factory()->create([
            'rol' => 'superusuario',
            'sucursal_id' => $sucursal->id,
        ]);

        // La pestaña de alta ya no expone Capturista ni Vendedor como opciones seleccionables.
        $this->actingAs($admin)
            ->get(route('usuarios.create'))
            ->assertOk()
            ->assertSee('value="usuario"', false)
            ->assertSee('value="superusuario"', false)
            ->assertSee('value="tecnico"', false)
            ->assertDontSee('value="capturista"', false)
            ->assertDontSee('value="vendedor"', false);

        // UsuarioController::store rechaza también un rol retirado enviado manualmente.
        $respuesta = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'ROL RETIRADO',
            'telefono' => '9990000000',
            'correo_contacto' => 'contacto@ejemplo.com',
            'email' => 'retirado@ejemplo.com',
            'rol' => 'vendedor',
            'sucursal_id' => $sucursal->id,
        ]);

        $respuesta->assertSessionHasErrors('rol');
        $this->assertDatabaseMissing('users', ['email' => 'retirado@ejemplo.com']);
    }

    /**
     * Verifica que el administrador global vea el control de contraseña,
     * pueda actualizarla sin elegir sucursal y utilice la nueva clave en LoginRequest.
     */
    public function test_global_superuser_can_update_its_password_without_a_branch(): void
    {
        // La migración de recuperación puede haber creado esta cuenta; updateOrCreate evita duplicarla en pruebas.
        $admin = User::updateOrCreate(['email' => 'admin@movilphone.com'], [
            'name' => 'ADMIN GLOBAL',
            'correo_contacto' => 'admin@movilphone.com',
            'rol' => 'superusuario',
            'sucursal_id' => null,
            'password' => Hash::make('ClaveAnterior2026'),
        ]);

        // La vista conecta el botón Actualizar contraseña con los campos enviados al controlador.
        $this->actingAs($admin)
            ->get(route('usuarios.edit', $admin))
            ->assertOk()
            ->assertSee('Actualizar contraseña')
            ->assertSee('Sin sucursal (cuenta global)');

        $respuesta = $this->actingAs($admin)->put(route('usuarios.update', $admin), [
            'name' => 'ADMIN GLOBAL',
            'telefono' => '',
            'correo_contacto' => 'admin@movilphone.com',
            'email' => 'admin@movilphone.com',
            'rol' => 'superusuario',
            'sucursal_id' => '',
            'password' => 'NuevaClaveAdmin2026',
            'password_confirmation' => 'NuevaClaveAdmin2026',
        ]);

        $respuesta->assertRedirect(route('usuarios.index'));
        $respuesta->assertSessionHas('success', 'Usuario y contraseña actualizados correctamente.');
        $this->assertNull($admin->refresh()->sucursal_id);
        $this->assertTrue(Hash::check('NuevaClaveAdmin2026', $admin->password));

        // Confirma que la credencial nueva abre la sesión administrativa real.
        auth()->logout();
        $this->post('/login', [
            'email' => 'admin@movilphone.com',
            'password' => 'NuevaClaveAdmin2026',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }
}
