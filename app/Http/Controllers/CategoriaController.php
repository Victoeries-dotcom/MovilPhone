<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\OrdenServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\AdminActivityLogger;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $filtro = $request->get('filtro');
        $sucursalId = session('sucursal_id') ?: auth()->user()?->sucursal_id;

        // withCount(['ordenes', 'productos']): calcula, en una sola consulta,
        // cuántas Órdenes de Servicio y cuántas piezas de Inventario tiene
        // cada categoría (esto llena $categoria->ordenes_count y
        // $categoria->productos_count, usados en los badges).
        //
        // with('productos'): además trae la LISTA completa de esas piezas
        // (no solo el número), necesaria para pintar las columnas nuevas
        // de nombre, dispositivo compatible y calidad en la tabla.
        $categorias = Categoria::query()
            ->withCount([
                'ordenes as ordenes_count' => fn ($query) => $query->where('sucursal_id', $sucursalId),
                'productos as productos_count' => fn ($query) => $query->where('sucursal_id', $sucursalId),
            ])
            ->with(['productos' => fn ($query) => $query->where('sucursal_id', $sucursalId)->orderBy('nombre')])
            ->orderBy('nombre')
            ->get();

        $ordenes = null;
        $productos = collect();
        if ($filtro) {
            // Cuando el usuario da clic en un botón de categoría específica,
            // se filtran las Órdenes de Servicio que pertenecen a esa categoría.
            $ordenes = OrdenServicio::where('categoria', $filtro)
                ->where('sucursal_id', $sucursalId)
                ->with(['cliente', 'sucursal'])
                ->get();

            // También se cargan las piezas de Inventario de la categoría filtrada
            // para que la tabla "Piezas en inventario" tenga datos disponibles.
            $productos = Inventario::with('sucursal')
                ->where('categoria', $filtro)
                ->where('sucursal_id', $sucursalId)
                ->orderBy('nombre')
                ->get();
        }

        return view('categorias.index', compact('categorias', 'filtro', 'ordenes', 'productos'));
    }

    public function create()
    {
        // Muestra el formulario vacío para crear una categoría nueva manualmente.
        return view('categorias.create');
    }

    public function store(Request $request)
    {
        // Valida que 'nombre' venga sí o sí, y 'descripcion' es opcional.
        $request->validate([
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        // Normaliza el registro en mayúsculas y lo conecta explícitamente con la tabla categorias.
        $categoria = Categoria::create([
            'nombre' => Str::upper($request->nombre),
            'descripcion' => $request->filled('descripcion')
                ? Str::upper($request->descripcion)
                : null,
        ]);

        AdminActivityLogger::registrar('CATEGORIAS', 'CREAR', 'Categoria '.$categoria->nombre.' creada.', session('sucursal_id'), $categoria);

        return redirect()->route('categorias.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(Categoria $categoria)
    {
        // Muestra el formulario de edición, precargado con los datos
        // de la categoría que se va a modificar.
        return view('categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        // Actualiza la categoría existente con los nuevos datos.
        $categoria->update([
            'nombre' => Str::upper($request->nombre),
            'descripcion' => $request->filled('descripcion') ? Str::upper($request->descripcion) : null,
        ]);
        AdminActivityLogger::registrar('CATEGORIAS', 'EDITAR', 'Categoria '.$categoria->nombre.' actualizada.', session('sucursal_id'), $categoria);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Categoria $categoria)
    {
        // Elimina la categoría seleccionada.
        AdminActivityLogger::registrar('CATEGORIAS', 'ELIMINAR', 'Categoria '.$categoria->nombre.' eliminada.', session('sucursal_id'), $categoria);
        $categoria->delete();
        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada correctamente.');
    }
}
