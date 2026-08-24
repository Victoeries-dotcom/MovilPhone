<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use JsonException;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Throwable;

class SystemBackupExcelService
{
    /** Orden y nombres claros de las secciones operativas incluidas en el Excel. */
    private const SECCIONES = [
        'sucursales' => 'Sucursales',
        'users' => 'Usuarios',
        'clientes' => 'Clientes',
        'ordenes_servicio' => 'Órdenes de servicio',
        'historial_estados' => 'Historial de órdenes',
        'inventario' => 'Inventario',
        'categorias' => 'Categorías',
        'ventas' => 'Ventas',
        'venta_detalles' => 'Detalle de ventas',
        'movimientos_caja' => 'Caja',
        'admin_activities' => 'Actividad',
        'configuraciones' => 'Configuración',
        'device_catalog_entries' => 'Catálogo de equipos',
    ];

    /**
     * Convierte un respaldo JSON existente en un XLSX temporal organizado por secciones.
     * El archivo resultante es solo de consulta; el JSON original permanece intacto.
     */
    public function create(string $rutaRespaldo): string
    {
        $disco = Storage::disk('local');
        $contenido = $this->decodificar($disco->get($rutaRespaldo));
        $directorioTemporal = storage_path('app/private/tmp');
        File::ensureDirectoryExists($directorioTemporal);

        $rutaTemporal = tempnam($directorioTemporal, 'movilphone-excel-');
        if ($rutaTemporal === false) {
            throw new RuntimeException('No fue posible preparar el archivo Excel temporal.');
        }

        $rutaExcel = $rutaTemporal.'.xlsx';
        File::move($rutaTemporal, $rutaExcel);

        try {
            $this->escribirLibro($rutaExcel, $contenido, $directorioTemporal);
        } catch (Throwable $error) {
            File::delete($rutaExcel);
            throw $error;
        }

        return $rutaExcel;
    }

    /** Valida que el JSON conserve la estructura metadata/tablas creada por SystemBackupService. */
    private function decodificar(string $json): array
    {
        try {
            $contenido = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('El respaldo seleccionado no contiene un JSON válido.', previous: $error);
        }

        if (! is_array($contenido)
            || ! is_array($contenido['metadata'] ?? null)
            || ! is_array($contenido['tablas'] ?? null)) {
            throw new RuntimeException('El respaldo seleccionado no tiene la estructura esperada.');
        }

        return $contenido;
    }

    /** Construye el libro con Resumen primero y una hoja por cada sección operativa. */
    private function escribirLibro(string $rutaExcel, array $contenido, string $directorioTemporal): void
    {
        $estiloPredeterminado = (new Style)
            ->setFontName('Arial')
            ->setFontSize(10)
            ->setFontColor(Color::rgb(15, 31, 61));

        $opciones = new Options;
        $opciones->DEFAULT_ROW_STYLE = $estiloPredeterminado;
        $opciones->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = false;
        $opciones->setTempFolder($directorioTemporal);
        $opciones->setProperties(new Properties(
            title: 'Respaldo MovilPhone por secciones',
            subject: 'Copia de consulta del respaldo del sistema',
            application: 'MovilPhone',
            creator: 'MovilPhone',
            lastModifiedBy: 'MovilPhone',
            description: 'Excel completo generado desde un respaldo JSON privado.',
            language: 'es-MX',
        ));

        $escritor = new Writer($opciones);
        $escritor->openToFile($rutaExcel);

        try {
            $tablas = $this->tablasOrdenadas($contenido['tablas']);
            $this->escribirResumen($escritor, $contenido['metadata'], $tablas);

            foreach ($tablas as $tabla => $filas) {
                $hoja = $escritor->addNewSheetAndMakeItCurrent();
                $hoja->setName($this->nombreHoja($tabla));
                $this->configurarVista($hoja, 4);
                $this->escribirSeccion($escritor, $tabla, $filas);
            }
        } finally {
            $escritor->close();
        }
    }

    /** Presenta metadatos y cantidades para entender el contenido antes de abrir las demás hojas. */
    private function escribirResumen(Writer $escritor, array $metadata, array $tablas): void
    {
        $hoja = $escritor->getCurrentSheet();
        $hoja->setName('Resumen');
        $this->configurarVista($hoja, 5);
        $hoja->setColumnWidth(32, 1);
        $hoja->setColumnWidth(45, 2);
        $hoja->setColumnWidth(18, 3);

        $escritor->addRow(Row::fromValues(['RESPALDO MOVILPHONE', ''], $this->estiloTitulo()));
        $escritor->addRow(Row::fromValues([
            'Uso del archivo',
            'Copia completa del JSON organizada por hojas. Contiene información técnica y sensible; protégela como el respaldo original.',
        ], $this->estiloAviso()));
        $escritor->addRow(Row::fromValues(['', '']));
        $escritor->addRow(Row::fromValues(['Campo JSON', 'Valor'], $this->estiloEncabezado()));

        $indice = 0;
        foreach ($metadata as $campo => $valor) {
            [$valorCelda, $estiloCelda] = $this->valorCelda((string) $campo, $valor);
            $escritor->addRow(new Row([
                Cell::fromValue((string) $campo, $this->estiloTexto()),
                Cell::fromValue($valorCelda, $estiloCelda),
            ], $this->estiloFila($indice % 2 === 1)));
            $indice++;
        }

        $escritor->addRow(Row::fromValues(['', '']));
        $escritor->addRow(Row::fromValues(['Tabla JSON', 'Hoja Excel', 'Registros'], $this->estiloEncabezado()));

        $indice = 0;
        foreach ($tablas as $tabla => $filas) {
            $escritor->addRow(Row::fromValues([
                $tabla,
                $this->nombreHoja($tabla),
                count($filas),
            ], $this->estiloFila($indice % 2 === 1)));
            $indice++;
        }
    }

    /** Agrega título, encabezados, filtros y filas tipadas a una sección del respaldo. */
    private function escribirSeccion(Writer $escritor, string $tabla, array $filas): void
    {
        // Cada fila y columna del JSON se conserva; la separación por hojas solo mejora su lectura.
        $filasCompletas = array_map(
            fn (mixed $fila): array => is_array($fila) ? $fila : (array) $fila,
            $filas
        );
        $encabezados = $this->encabezados($filasCompletas);
        $totalColumnas = max(1, count($encabezados));

        $escritor->addRow(Row::fromValues(
            array_pad([mb_strtoupper($this->nombreHoja($tabla))], $totalColumnas, ''),
            $this->estiloTitulo()
        ));
        $escritor->addRow(Row::fromValues(
            array_pad(['Registros', count($filasCompletas)], $totalColumnas, ''),
            $this->estiloAviso()
        ));

        if ($encabezados === []) {
            $escritor->addRow(Row::fromValues(['Sin registros'], $this->estiloEncabezado()));
            $escritor->getCurrentSheet()->setColumnWidth(24, 1);

            return;
        }

        $escritor->addRow(Row::fromValues($encabezados, $this->estiloEncabezado()));
        $this->configurarAnchos($escritor, $encabezados, $filasCompletas);

        foreach ($filasCompletas as $indice => $fila) {
            $alternada = $indice % 2 === 1;
            $celdas = [];
            foreach ($encabezados as $columna) {
                [$valor, $estilo] = $this->valorCelda($columna, $fila[$columna] ?? null);
                $celdas[] = Cell::fromValue($valor, $estilo);
            }
            $escritor->addRow(new Row($celdas, $this->estiloFila($alternada)));
        }

        $escritor->getCurrentSheet()->setAutoFilter(new AutoFilter(
            0,
            3,
            count($encabezados) - 1,
            3 + count($filasCompletas)
        ));
    }

    /** Conserva todas las tablas y ordena primero las secciones reconocidas por el sistema. */
    private function tablasOrdenadas(array $tablas): array
    {
        $ordenadas = array_filter(
            $tablas,
            fn (mixed $filas): bool => is_array($filas)
        );

        uksort($ordenadas, function (string $izquierda, string $derecha): int {
            $orden = array_flip(array_keys(self::SECCIONES));

            return ($orden[$izquierda] ?? PHP_INT_MAX) <=> ($orden[$derecha] ?? PHP_INT_MAX)
                ?: $izquierda <=> $derecha;
        });

        return $ordenadas;
    }

    /** Reúne encabezados estables incluso si filas posteriores agregan campos opcionales. */
    private function encabezados(array $filas): array
    {
        $encabezados = [];
        foreach ($filas as $fila) {
            foreach (array_keys($fila) as $columna) {
                if (! in_array($columna, $encabezados, true)) {
                    $encabezados[] = $columna;
                }
            }
        }

        return $encabezados;
    }

    /** Conserva el valor del JSON y aplica formato visual sin modificar su contenido. */
    private function valorCelda(string $columna, mixed $valor): array
    {
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode(
                $valor,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        if (is_string($valor) && $this->esIdentificador($columna)) {
            return [$valor, $this->estiloTexto()];
        }

        if ((is_int($valor) || is_float($valor)) && $this->esMoneda($columna)) {
            return [(float) $valor, $this->estiloMoneda()];
        }

        return [$valor, null];
    }

    /** Ajusta anchos con límites legibles para evitar columnas recortadas o excesivamente grandes. */
    private function configurarAnchos(Writer $escritor, array $encabezados, array $filas): void
    {
        foreach ($encabezados as $indice => $columna) {
            $maximo = mb_strlen((string) $columna);
            foreach ($filas as $fila) {
                $valor = $fila[$columna] ?? '';
                if (is_scalar($valor)) {
                    $maximo = max($maximo, mb_strlen((string) $valor));
                }
            }

            // Fechas y cantidades formateadas ocupan más espacio que su valor crudo dentro del JSON.
            $anchoMinimo = match (true) {
                $this->esFecha($columna) => 22,
                $this->esMoneda($columna) => 16,
                $this->esIdentificador($columna) => 16,
                default => 12,
            };

            $escritor->getCurrentSheet()->setColumnWidth(
                min(45, max($anchoMinimo, $maximo + 2)),
                $indice + 1
            );
        }
    }

    /** Configura una lectura cómoda: encabezados congelados, zoom moderado y cuadrícula limpia. */
    private function configurarVista(mixed $hoja, int $filaCongelada): void
    {
        $vista = (new SheetView)
            ->setFreezeRow($filaCongelada)
            ->setShowGridLines(false)
            ->setZoomScale(90);
        $hoja->setSheetView($vista);
    }

    /** Genera nombres válidos para Excel y conserva los títulos reconocibles del sistema. */
    private function nombreHoja(string $tabla): string
    {
        $nombre = self::SECCIONES[$tabla] ?? str_replace('_', ' ', mb_convert_case($tabla, MB_CASE_TITLE, 'UTF-8'));
        $nombre = preg_replace('/[\\[\\]:*?\\/\\\\]/u', '-', $nombre) ?: 'Datos';

        return mb_substr($nombre, 0, 31);
    }

    private function esIdentificador(string $columna): bool
    {
        return $columna === 'id'
            || str_ends_with($columna, '_id')
            || preg_match('/telefono|imei|serie|codigo|folio|sku|uuid/i', $columna) === 1;
    }

    private function esFecha(string $columna): bool
    {
        return str_ends_with($columna, '_at')
            || preg_match('/^(fecha|fecha_|.*_fecha$)/i', $columna) === 1;
    }

    private function esMoneda(string $columna): bool
    {
        return preg_match('/monto|precio|costo|total|saldo|anticipo|ingreso|egreso/i', $columna) === 1;
    }

    private function estiloTitulo(): Style
    {
        return (new Style)
            ->setBackgroundColor(Color::rgb(15, 31, 61))
            ->setFontColor(Color::WHITE)
            ->setFontBold()
            ->setFontSize(15)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    private function estiloAviso(): Style
    {
        return (new Style)
            ->setBackgroundColor(Color::rgb(237, 233, 254))
            ->setFontColor(Color::rgb(79, 70, 229))
            ->setShouldWrapText();
    }

    private function estiloEncabezado(): Style
    {
        return (new Style)
            ->setBackgroundColor(Color::rgb(108, 76, 246))
            ->setFontColor(Color::WHITE)
            ->setFontBold()
            ->setCellAlignment(CellAlignment::LEFT)
            ->setShouldWrapText();
    }

    private function estiloFila(bool $alternada): Style
    {
        $estilo = new Style;
        if ($alternada) {
            $estilo->setBackgroundColor(Color::rgb(246, 248, 251));
        }

        return $estilo;
    }

    private function estiloTexto(): Style
    {
        return (new Style)->setFormat('@');
    }

    private function estiloMoneda(): Style
    {
        return (new Style)->setFormat('$#,##0.00');
    }
}
