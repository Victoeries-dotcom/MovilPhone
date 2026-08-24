<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class BackupExcelDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const ARCHIVO = 'movilphone-manual-2026-08-23-184445.json';

    protected function setUp(): void
    {
        parent::setUp();

        // Aísla los archivos del respaldo para que la prueba nunca toque copias reales del sistema.
        Storage::fake('local');
    }

    /**
     * Comprueba el flujo completo: botón visible, descarga XLSX y hojas separadas sin secretos.
     * Se conecta con la vista de respaldos, BackupController y el exportador de consulta.
     */
    public function test_superusuario_descarga_el_respaldo_en_excel_por_secciones(): void
    {
        $superusuario = User::factory()->create(['rol' => 'superusuario']);
        $this->guardarRespaldoDePrueba();

        $this->actingAs($superusuario)
            ->get(route('respaldos.index'))
            ->assertOk()
            ->assertSee('Excel')
            ->assertSee(route('respaldos.excel', self::ARCHIVO), false);

        $respuesta = $this->actingAs($superusuario)
            ->get(route('respaldos.excel', self::ARCHIVO));

        $respuesta
            ->assertOk()
            ->assertDownload('movilphone-manual-2026-08-23-184445.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // La conversión es adicional: el JSON restaurable continúa disponible sin alteraciones.
        $this->actingAs($superusuario)
            ->get(route('respaldos.download', self::ARCHIVO))
            ->assertOk()
            ->assertDownload(self::ARCHIVO);

        // Lee el archivo entregado por Laravel para probar su estructura real y no solo la extensión.
        $rutaExcel = $respuesta->baseResponse->getFile()->getPathname();
        $hojas = $this->leerHojas($rutaExcel);

        $this->assertSame(['Resumen', 'Usuarios', 'Clientes', 'Caja'], array_keys($hojas));
        $this->assertArrayNotHasKey('Sesiones', $hojas);

        $encabezadosUsuarios = $hojas['Usuarios'][2];
        $this->assertSame(['id', 'name', 'telefono', 'email'], $encabezadosUsuarios);
        $this->assertNotContains('password', $encabezadosUsuarios);
        $this->assertNotContains('remember_token', $encabezadosUsuarios);
        $this->assertSame('0012345678', $hojas['Usuarios'][3][2]);

        $this->assertSame(['id', 'nombre', 'telefono', 'created_at'], $hojas['Clientes'][2]);
        $this->assertSame('0000456789', $hojas['Clientes'][3][2]);
        $this->assertSame(125.50, $hojas['Caja'][3][1]);
    }

    /** Mantiene la descarga administrativa protegida aunque se escriba la URL directamente. */
    public function test_usuario_operativo_no_puede_descargar_el_excel_del_respaldo(): void
    {
        $usuario = User::factory()->create(['rol' => 'usuario']);
        $this->guardarRespaldoDePrueba();

        $this->actingAs($usuario)
            ->get(route('respaldos.excel', self::ARCHIVO))
            ->assertForbidden();
    }

    /** Guarda un JSON pequeño que representa tablas operativas y datos técnicos sensibles. */
    private function guardarRespaldoDePrueba(): void
    {
        $contenido = [
            'metadata' => [
                'aplicacion' => 'MovilPhone',
                'base_datos' => 'movilphone',
                'creado_en' => '2026-08-23T18:44:45-06:00',
                'origen' => 'manual',
                'version' => 1,
            ],
            'tablas' => [
                'users' => [[
                    'id' => 1,
                    'name' => 'Administradora',
                    'telefono' => '0012345678',
                    'email' => 'admin@movilphone.test',
                    'password' => 'hash-que-no-debe-aparecer',
                    'remember_token' => 'token-que-no-debe-aparecer',
                ]],
                'clientes' => [[
                    'id' => 5,
                    'nombre' => 'Ana López',
                    'telefono' => '0000456789',
                    'created_at' => '2026-08-23 10:15:00',
                ]],
                'movimientos_caja' => [[
                    'id' => 9,
                    'monto' => 125.50,
                    'concepto' => 'Venta de accesorio',
                    'fecha' => '2026-08-23',
                ]],
                'sessions' => [[
                    'id' => 'sesion-privada',
                    'payload' => 'contenido-sensible',
                ]],
            ],
        ];

        Storage::disk('local')->put(
            'backups/'.self::ARCHIVO,
            json_encode($contenido, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Abre el XLSX con un lector independiente y devuelve sus valores por nombre de hoja.
     * Esta lectura detecta archivos ZIP corruptos, hojas faltantes y datos mal acomodados.
     *
     * @return array<string, array<int, array<int, mixed>>>
     */
    private function leerHojas(string $ruta): array
    {
        $lector = new Reader;
        $lector->open($ruta);
        $hojas = [];

        foreach ($lector->getSheetIterator() as $hoja) {
            $filas = [];
            foreach ($hoja->getRowIterator() as $fila) {
                $filas[] = $fila->toArray();
            }
            $hojas[$hoja->getName()] = $filas;
        }

        $lector->close();

        return $hojas;
    }
}
