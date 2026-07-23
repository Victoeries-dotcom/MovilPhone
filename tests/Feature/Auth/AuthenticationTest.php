<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertStatus(200)
            // Confirma que la nueva interfaz conserve el formulario conectado a LoginRequest.
            ->assertSee('Bienvenido de nuevo')
            ->assertSee('Tu taller,')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('password-toggle', false)
            ->assertSee('images/movilphone-logo-final.png', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_administrator_and_created_user_keep_their_existing_credentials(): void
    {
        /*
         * Verifica que la nueva interfaz siga conectada con la autenticación de Laravel
         * para los dos perfiles reales del sistema, sin alterar correos ni contraseñas.
         */
        foreach (['superusuario', 'usuario'] as $rol) {
            $user = User::factory()->create(['rol' => $rol]);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('dashboard', absolute: false));

            $this->assertAuthenticatedAs($user);

            // Cierra cada sesión para comprobar el siguiente perfil de forma independiente.
            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');

        // La raíz protegida redirige al mismo acceso profesional después de cerrar la sesión.
        $this->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertSee('Bienvenido de nuevo');
    }
}
