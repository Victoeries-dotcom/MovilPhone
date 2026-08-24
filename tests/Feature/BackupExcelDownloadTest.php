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
     * Comprueba que el XLSX conserve todas las secciones, campos y registros del JSON original.
     * Se conecta con la vista de respaldos, BackupController y el exportador de consulta.
     */
    public function test_superusuario_descarga_un_excel_completo_y_organizado_por_secciones(): void
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
        $contenidoJson = json_decode(
            Storage::disk('local')->get('backups/'.self::ARCHIVO),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // La comparación integral evita que una tabla, columna o valor se omita silenciosamente.
        $this->assertExcelConservaContenidoJson($contenidoJson, $hojas);

        $this->assertSame(
            ['Resumen', 'Usuarios', 'Clientes', 'Caja', 'Jobs', 'Migrations', 'Sessions'],
            array_keys($hojas)
        );
        $this->assertTrue($this->contieneFila($hojas['Resumen'], ['zona_horaria', 'America/Mexico_City']));
        $this->assertTrue($this->contieneFila($hojas['Resumen'], ['sessions', 'Sessions', 1]));

        $encabezadosUsuarios = $hojas['Usuarios'][2];
        $this->assertSame(
            ['id', 'name', 'telefono', 'email', 'password', 'remember_token'],
            $encabezadosUsuarios
        );
        $this->assertSame('0012345678', $hojas['Usuarios'][3][2]);
        $this->assertSame('hash-conservado-del-json', $hojas['Usuarios'][3][4]);
        $this->assertSame('token-conservado-del-json', $hojas['Usuarios'][3][5]);

        $this->assertSame(['id', 'nombre', 'telefono', 'created_at'], $hojas['Clientes'][2]);
        $this->assertSame('0000456789', $hojas['Clientes'][3][2]);
        $this->assertSame('125.50', $hojas['Caja'][3][1]);
        $this->assertSame(['Sin registros'], $hojas['Jobs'][2]);
        $this->assertSame(['id', 'migration', 'batch'], $hojas['Migrations'][2]);
        $this->assertSame(['id', 'user_id', 'payload', 'contexto'], $hojas['Sessions'][2]);
        $this->assertSame('contenido-completo-de-la-sesion', $hojas['Sessions'][3][2]);
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
                'zona_horaria' => 'America/Mexico_City',
                'opciones' => ['completo' => true, 'formato' => 'xlsx'],
            ],
            'tablas' => [
                'users' => [[
                    'id' => 1,
                    'name' => 'Administradora',
                    'telefono' => '0012345678',
                    'email' => 'admin@movilphone.test',
                    'password' => 'hash-conservado-del-json',
                    'remember_token' => 'token-conservado-del-json',
                ]],
                'clientes' => [[
                    'id' => 5,
                    'nombre' => 'Ana López',
                    'telefono' => '0000456789',
                    'created_at' => '2026-08-23 10:15:00',
                ]],
                'movimientos_caja' => [[
                    'id' => 9,
                    'monto' => '125.50',
                    'concepto' => 'Venta de accesorio',
                    'fecha' => '2026-08-23',
                ]],
                'jobs' => [],
                'migrations' => [[
                    'id' => 1,
                    'migration' => '2026_08_23_000000_ejemplo',
                    'batch' => 1,
                ]],
                'sessions' => [[
                    'id' => 'sesion-privada',
                    'user_id' => 1,
                    'payload' => 'contenido-completo-de-la-sesion',
                    'contexto' => ['ip' => '127.0.0.1', 'recordar' => true],
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

    /** Comprueba una fila por sus primeras columnas sin depender de su posición en el resumen. */
    private function contieneFila(array $filas, array $esperada): bool
    {
        foreach ($filas as $fila) {
            if (array_slice($fila, 0, count($esperada)) === $esperada) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recorre el JSON completo y demuestra que cada elemento tiene una representación en el XLSX.
     * Las estructuras anidadas se conservan como JSON dentro de una celda, sin perder sus valores.
     */
    private function assertExcelConservaContenidoJson(array $contenidoJson, array $hojas): void
    {
        foreach ($contenidoJson['metadata'] as $campo => $valor) {
            $this->assertTrue($this->contieneFila($hojas['Resumen'], [
                $campo,
                $this->valorEsperadoEnExcel($valor),
            ]), "Falta el metadato {$campo} en el Excel.");
        }

        $mapaTablas = $this->mapaTablasDesdeResumen($hojas['Resumen']);
        $tablasJson = array_keys($contenidoJson['tablas']);
        $tablasExcel = array_keys($mapaTablas);
        sort($tablasJson);
        sort($tablasExcel);
        $this->assertSame($tablasJson, $tablasExcel);

        foreach ($contenidoJson['tablas'] as $tabla => $filasJson) {
            $nombreHoja = $mapaTablas[$tabla]['hoja'];
            $this->assertArrayHasKey($nombreHoja, $hojas);
            $this->assertSame(count($filasJson), $mapaTablas[$tabla]['registros']);

            if ($filasJson === []) {
                $this->assertSame(['Sin registros'], $hojas[$nombreHoja][2]);

                continue;
            }

            $encabezados = [];
            foreach ($filasJson as $filaJson) {
                foreach (array_keys($filaJson) as $columna) {
                    if (! in_array($columna, $encabezados, true)) {
                        $encabezados[] = $columna;
                    }
                }
            }

            $this->assertSame($encabezados, $hojas[$nombreHoja][2]);
            foreach ($filasJson as $indice => $filaJson) {
                $filaEsperada = array_map(
                    fn (string $columna): mixed => $this->valorEsperadoEnExcel($filaJson[$columna] ?? null),
                    $encabezados
                );
                $this->assertSame($filaEsperada, $hojas[$nombreHoja][$indice + 3]);
            }
        }
    }

    /** Obtiene del resumen la correspondencia exacta entre tabla JSON y hoja Excel. */
    private function mapaTablasDesdeResumen(array $filasResumen): array
    {
        $mapa = [];
        $leyendoTablas = false;

        foreach ($filasResumen as $fila) {
            if (($fila[0] ?? null) === 'Tabla JSON') {
                $leyendoTablas = true;

                continue;
            }

            if ($leyendoTablas && isset($fila[0], $fila[1], $fila[2])) {
                $mapa[(string) $fila[0]] = [
                    'hoja' => (string) $fila[1],
                    'registros' => (int) $fila[2],
                ];
            }
        }

        return $mapa;
    }

    /** Normaliza únicamente los valores que Excel no puede guardar como una estructura anidada. */
    private function valorEsperadoEnExcel(mixed $valor): mixed
    {
        if (is_array($valor) || is_object($valor)) {
            return json_encode(
                $valor,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        return $valor;
    }
}
