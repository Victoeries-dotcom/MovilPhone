<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Confirma que el panel principal exige autenticacion.
     * Se conecta con el middleware auth de la ruta home y con la pantalla de acceso.
     */
    public function test_the_dashboard_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
