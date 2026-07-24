<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Support\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SucursalController extends Controller
{
    /**
     * Cambia la sucursal activa desde el menú lateral.
     * Se conecta con sucursales.id y guarda el contexto usado por Ventas,
     * Inventario, Caja, Clientes, Usuarios, Órdenes y Reportes.
     */
    public function cambiar(Request $request)
    {
        // Solo el Super Usuario puede consultar información de otra sucursal.
        abort_unless($request->user()?->rol === 'superusuario', 403);

        $datos = $request->validate([
            'sucursal_id' => 'required|integer|exists:sucursales,id',
        ]);

        $sucursal = Sucursal::findOrFail($datos['sucursal_id']);

        // La sesión conserva la selección mientras el usuario navega por el sistema.
        session([
            'sucursal_id' => $sucursal->id,
            'sucursal_nombre' => $sucursal->nombre,
        ]);

        return back()->with('success', 'Sucursal activa: '.$sucursal->nombre.'.');
    }

    public function index(Request $request)
    {
        $sucursales = Sucursal::all();

        // Al recibir una sucursal desde la tabla la guarda como contexto activo para todo el sistema.
        if ($request->filled('sucursal_id')) {
            $sucursalSeleccionada = Sucursal::find($request->sucursal_id);
            if ($sucursalSeleccionada) {
                session([
                    'sucursal_id' => $sucursalSeleccionada->id,
                    'sucursal_nombre' => $sucursalSeleccionada->nombre,
                ]);
            }
        } else {
            // Conserva visible la selección guardada y se conecta con Inventario, Caja, Clientes y Usuarios.
            $sucursalSeleccionada = session('sucursal_id')
                ? Sucursal::find(session('sucursal_id'))
                : null;

            if (! $sucursalSeleccionada) {
                // Limpia una sesión inválida si la sucursal fue eliminada previamente.
                session()->forget(['sucursal_id', 'sucursal_nombre']);
            }
        }

        return view('sucursales.index', [
            'sucursales' => $sucursales,
            'sucursalSeleccionada' => $sucursalSeleccionada,
        ]);
    }

    public function create()
    {
        return view('sucursales.create');
    }

    public function store(Request $request)
    {
        $sucursal = new Sucursal;
        // Guarda textos principales en MAYÚSCULAS para mantener uniforme el registro de sucursales.
        $sucursal->nombre = Str::upper($request->nombre);
        $sucursal->ubicacion = $request->filled('ubicacion') ? Str::upper($request->ubicacion) : null;
        // La URL se conserva tal como viene porque Google Maps puede depender del enlace exacto.
        $sucursal->ubicacion_url = $request->ubicacion_url;
        $sucursal->nombre_encargado = $request->filled('nombre_encargado') ? Str::upper($request->nombre_encargado) : null;
        $sucursal->telefono_encargado = $request->telefono_encargado;
        $sucursal->horario = $request->filled('horario') ? Str::upper($request->horario) : null;
        $sucursal->save();
        AdminActivityLogger::registrar('SUCURSALES', 'CREAR', 'Sucursal '.$sucursal->nombre.' creada.', $sucursal->id, $sucursal);

        return redirect()->route('sucursales.index')->with('success', 'Sucursal creada.');
    }

    /**
     * Carga el formulario con los datos actuales de la sucursal.
     * Se conecta con la ruta sucursales.edit y la vista sucursales.edit.
     */
    public function edit(Sucursal $sucursal)
    {
        return view('sucursales.edit', compact('sucursal'));
    }

    /**
     * Valida y actualiza la sucursal seleccionada.
     * Se conecta con sucursales.update, la tabla sucursales y la sesión activa.
     */
    public function update(Request $request, Sucursal $sucursal)
    {
        // Estas reglas protegen los campos que se guardan en la tabla sucursales.
        $datos = $request->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'ubicacion_url' => 'nullable|url|max:2048',
            'nombre_encargado' => 'nullable|string|max:255',
            'telefono_encargado' => 'nullable|string|max:50',
            'horario' => 'nullable|string|max:100',
        ]);

        // Los textos se normalizan en mayúsculas; la URL conserva su formato original.
        $sucursal->nombre = Str::upper($datos['nombre']);
        $sucursal->ubicacion = filled($datos['ubicacion'] ?? null) ? Str::upper($datos['ubicacion']) : null;
        $sucursal->ubicacion_url = $datos['ubicacion_url'] ?? null;
        $sucursal->nombre_encargado = filled($datos['nombre_encargado'] ?? null) ? Str::upper($datos['nombre_encargado']) : null;
        $sucursal->telefono_encargado = $datos['telefono_encargado'] ?? null;
        $sucursal->horario = filled($datos['horario'] ?? null) ? Str::upper($datos['horario']) : null;
        $sucursal->save();

        // Si se editó la sucursal activa, refresca el nombre mostrado en todo el sistema.
        if ((int) session('sucursal_id') === (int) $sucursal->id) {
            session(['sucursal_nombre' => $sucursal->nombre]);
        }

        // Registra el cambio en la actividad administrativa para conservar trazabilidad.
        AdminActivityLogger::registrar(
            'SUCURSALES',
            'EDITAR',
            'Sucursal '.$sucursal->nombre.' actualizada.',
            $sucursal->id,
            $sucursal
        );

        return redirect()
            ->route('sucursales.index', ['sucursal_id' => $sucursal->id])
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Sucursal $sucursal)
    {
        try {
            $nombre = $sucursal->nombre;
            // La bitacora se guarda antes de borrar las relaciones vinculadas a la sucursal.
            AdminActivityLogger::registrar('SUCURSALES', 'ELIMINAR', 'Sucursal '.$nombre.' y sus datos relacionados eliminados.', $sucursal->id, $sucursal);
            foreach ($sucursal->ventas as $venta) {
                $venta->detalles()->delete();
            }
            $sucursal->ventas()->delete();
            foreach ($sucursal->ordenesServicio as $orden) {
                $orden->historial()->delete();
                $orden->movimientosCaja()->delete();
            }
            $sucursal->ordenesServicio()->delete();
            $sucursal->movimientosCaja()->delete();
            $sucursal->inventarios()->delete();
            $sucursal->delete();

            return redirect()->route('sucursales.index')->with('success', 'Sucursal eliminada.');
        } catch (\Exception $e) {
            return redirect()->route('sucursales.index')->with('error', 'Error: '.$e->getMessage());
        }
    }
}
