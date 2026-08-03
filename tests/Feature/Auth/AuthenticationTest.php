<?php

namespace Tests\Feature\Auth;

use App\Mail\FailedLoginAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_third_invalid_password_sends_alert_and_blocks_account_for_five_minutes(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'acceso@movilphone.test',
            'correo_contacto' => 'seguridad@movilphone.test',
        ]);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('password');
        }

        Mail::assertNothingSent();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors([
            'password' => 'Demasiados intentos incorrectos. Vuelve a intentarlo en 5 minutos.',
        ]);

        Mail::assertSent(FailedLoginAlert::class, function (FailedLoginAlert $mail): bool {
            $contenido = $mail->render();

            return $mail->hasTo('seguridad@movilphone.test')
                && $mail->loginEmail === 'acceso@movilphone.test'
                && $mail->ipAddress === '127.0.0.1'
                && str_contains($contenido, '3 contraseñas incorrectas')
                && str_contains($contenido, '127.0.0.1');
        });

        // La contraseña correcta tampoco evade el bloqueo hasta que termine el plazo.
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_successful_login_resets_previous_failed_attempts(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->post('/logout');

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        Mail::assertNothingSent();
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
