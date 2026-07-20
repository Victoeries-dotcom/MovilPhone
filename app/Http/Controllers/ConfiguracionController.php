<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AdminActivityLogger;

class ConfiguracionController extends Controller
{
    /**
     * Muestra la identidad comercial y preferencias generales del sistema.
     * Se conecta con configuraciones y con AppServiceProvider para aplicarse en todas las vistas.
     */
    public function edit()
    {
        $claves = [
            'negocio_nombre',
            'negocio_subtitulo',
            'negocio_telefono',
            'negocio_email',
            'negocio_direccion',
            'color_primario',
            'moneda',
            'impuesto_porcentaje',
            'modo_demo',
        ];

        $configuracion = DB::table('configuraciones')
            ->whereIn('clave', $claves)
            ->pluck('valor', 'clave');

        return view('configuracion.index', compact('configuracion'));
    }

    /**
     * Guarda identidad, color, moneda, impuesto y modo demostracion.
     * Se conecta con layout, tickets, reportes y ProtectDemoMode.
     */
    public function update(Request $request)
    {
        $datos = $request->validate([
            'negocio_nombre' => 'required|string|max:80',
            'negocio_subtitulo' => 'nullable|string|max:120',
            'negocio_telefono' => 'nullable|string|max:30',
            'negocio_email' => 'nullable|email|max:120',
            'negocio_direccion' => 'nullable|string|max:250',
            'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'moneda' => 'required|in:MXN,USD',
            'impuesto_porcentaje' => 'required|numeric|min:0|max:100',
            'modo_demo' => 'nullable|boolean',
        ]);

        $datos['modo_demo'] = $request->boolean('modo_demo') ? '1' : '0';
        $this->guardarValores($datos);
        AdminActivityLogger::registrar('CONFIGURACION', 'EDITAR', 'Preferencias comerciales actualizadas.', session('sucursal_id'));

        return redirect()->route('configuracion.edit')
            ->with('success', 'Configuracion comercial actualizada correctamente.');
    }

    /**
     * Muestra la política que aparecerá en los tickets de entrega.
     * Se conecta con configuraciones.clave = politica_garantia.
     */
    public function editarGarantia()
    {
        $politica = DB::table('configuraciones')
            ->where('clave', 'politica_garantia')
            ->value('valor');

        return view('configuracion.garantia', compact('politica'));
    }

    /**
     * Guarda o crea la política sin depender de un registro precargado.
     * Se conecta con OrdenServicioController::ticketEntrega.
     */
    public function guardarGarantia(Request $request)
    {
        $request->validate([
            'politica_garantia' => 'required|string|max:3000',
        ]);

        $consulta = DB::table('configuraciones')->where('clave', 'politica_garantia');
        $datos = [
            'valor' => trim($request->politica_garantia),
            'updated_at' => now(),
        ];

        // created_at se asigna solo la primera vez para conservar la fecha original.
        if (!$consulta->exists()) {
            $datos['clave'] = 'politica_garantia';
            $datos['created_at'] = now();
            DB::table('configuraciones')->insert($datos);
        } else {
            $consulta->update($datos);
        }

        AdminActivityLogger::registrar('CONFIGURACION', 'EDITAR', 'Politica de garantia actualizada.', session('sucursal_id'));

        return redirect()->route('configuracion.garantia')
            ->with('success', 'Política de garantía actualizada correctamente.');
    }

    /**
     * Actualiza o crea varias claves sin modificar la fecha original del registro.
     * Se conecta con configuraciones y alimenta AppServiceProvider en la siguiente peticion.
     */
    private function guardarValores(array $valores): void
    {
        foreach ($valores as $clave => $valor) {
            $consulta = DB::table('configuraciones')->where('clave', $clave);

            if ($consulta->exists()) {
                $consulta->update(['valor' => $valor, 'updated_at' => now()]);
                continue;
            }

            DB::table('configuraciones')->insert([
                'clave' => $clave,
                'valor' => $valor,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
