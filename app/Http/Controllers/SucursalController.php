<?php
namespace App\Http\Controllers;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\AdminActivityLogger;
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

            if (!$sucursalSeleccionada) {
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
        $sucursal = new Sucursal();
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
            return redirect()->route('sucursales.index')->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
