<?php

namespace App\Http\Controllers;

use App\Models\Categoria; // Sincroniza la categoría escrita en Inventario con el catálogo de Categorías.
use App\Models\Inventario;
use App\Models\Sucursal; // Limita altas y ediciones a la sucursal activa del usuario.
use App\Support\AdminActivityLogger;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        // Obtiene la sucursal elegida en Sucursales; si no existe en sesión usa la asignada al usuario.
        // Este identificador se conecta con sucursales.id e inventario.sucursal_id.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        $sucursalActiva = $sucursalActivaId ? Sucursal::find($sucursalActivaId) : null;

        $query = Inventario::with('sucursal');

        if ($sucursalActiva) {
            // Fuerza la tabla a mostrar únicamente piezas de la sucursal activa, aunque la URL tenga otro filtro.
            $query->where('sucursal_id', $sucursalActiva->id);
        } else {
            // Sin una sucursal activa no mezcla inventarios; la pantalla solicitará seleccionar una sucursal.
            $query->whereRaw('1 = 0');
        }
        if ($request->bajo_stock) {
            $query->whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', '%'.$buscar.'%')
                    ->orWhere('dispositivo_compatible', 'like', '%'.$buscar.'%')
                    ->orWhere('categoria', 'like', '%'.$buscar.'%')
                    ->orWhere('proveedor', 'like', '%'.$buscar.'%');
            });
        }

        // Ordena el inventario para vender más fácil:
        // primero muestra piezas con stock disponible y debajo deja vendidas/agotadas.
        $inventario = $query
            ->orderByRaw('CASE WHEN cantidad_disponible > 0 THEN 0 ELSE 1 END')
            ->orderBy('nombre')
            ->get();
        // La misma consulta base se reutiliza en las tarjetas para que tabla y totales siempre coincidan.
        $statsQuery = Inventario::query();
        if ($sucursalActiva) {
            $statsQuery->where('sucursal_id', $sucursalActiva->id);
        } else {
            $statsQuery->whereRaw('1 = 0');
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'bajo' => (clone $statsQuery)
                ->whereColumn('cantidad_disponible', '<=', 'stock_minimo')
                ->count(),
            // Las existencias negativas representan piezas agotadas y no restan valor al inventario disponible.
            'valor' => (clone $statsQuery)
                ->selectRaw('SUM(CASE WHEN cantidad_disponible > 0 THEN cantidad_disponible * precio_costo ELSE 0 END) as total')
                ->value('total') ?? 0,
        ];

        return view('inventario.index', compact('inventario', 'stats', 'sucursalActiva'));
    }

    public function create()
    {
        // El alta solo muestra la sucursal activa y se conecta con el usuario autenticado.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        $sucursales = Sucursal::whereKey($sucursalActivaId)->get();

        return view('inventario.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        // Fuerza la sucursal del servidor antes de validar para impedir altas en otra sede.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_unless($sucursalActivaId, 422, 'Selecciona una sucursal antes de agregar inventario.');
        $request->merge(['sucursal_id' => (int) $sucursalActivaId]);

        // Valida la información que se conectará con inventario.
        $request->validate([
            'nombre' => 'required|string',
            'categoria' => 'required|string|max:100',
            'sucursal_id' => 'required|exists:sucursales,id',
            'cantidad_disponible' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'precio_costo' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
        ]);

        // Limpia espacios de la categoría manual antes de conectarla con Inventario y Categorías.
        $request->merge(['categoria' => trim($request->categoria)]);

        // 2. NUEVO: sincronizamos la categoría con la tabla `categorias`.
        //    Esto es lo que hace que, al agregar un producto, la categoría
        //    aparezca automáticamente en el módulo de Categorías.
        $this->sincronizarCategoria($request->categoria);

        // 3. Guardamos la pieza de inventario exactamente como antes.
        $pieza = Inventario::create($request->only([
            'nombre', 'categoria', 'sucursal_id',
            'cantidad_disponible', 'stock_minimo',
            'precio_costo', 'precio_venta',
            'proveedor', 'dispositivo_compatible', 'calidad',
        ]));

        // Auditoria: conecta la pieza creada con Actividad y con la campana administrativa.
        AdminActivityLogger::registrar('INVENTARIO', 'CREAR', 'Pieza '.$pieza->nombre.' agregada al inventario.', $pieza->sucursal_id, $pieza);

        return redirect()->route('inventario.index')->with('success', 'Pieza agregada correctamente.');
    }

    public function show(Inventario $inventario)
    {
        // La ficha usa la misma protección de edit y destroy para no cruzar sucursales por URL.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_unless((int) $inventario->sucursal_id === (int) $sucursalActivaId, 404);

        return view('inventario.show', compact('inventario'));
    }

    public function edit(Inventario $inventario)
    {
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_unless((int) $inventario->sucursal_id === (int) $sucursalActivaId, 404);
        // La edición conserva la sucursal original y no ofrece mover la pieza a otra sede.
        $sucursales = Sucursal::whereKey($sucursalActivaId)->get();

        return view('inventario.edit', compact('inventario', 'sucursales'));
    }

    public function update(Request $request, Inventario $inventario)
    {
        // Revalida la sede y sobrescribe cualquier sucursal manipulada desde el navegador.
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_unless((int) $inventario->sucursal_id === (int) $sucursalActivaId, 404);
        $request->merge(['sucursal_id' => (int) $sucursalActivaId]);

        $request->validate([
            'nombre' => 'required|string',
            'categoria' => 'required|string|max:100',
            'sucursal_id' => 'required|exists:sucursales,id',
            'cantidad_disponible' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'precio_costo' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
        ]);

        // Aplica la misma limpieza al editar para conservar un solo nombre por categoría.
        $request->merge(['categoria' => trim($request->categoria)]);

        // Mismo mecanismo que en store(): si al editar cambian la pieza
        // a una categoría nueva, también se crea automáticamente.
        $this->sincronizarCategoria($request->categoria);

        $inventario->update($request->only([
            'nombre', 'categoria', 'sucursal_id',
            'cantidad_disponible', 'stock_minimo',
            'precio_costo', 'precio_venta',
            'proveedor', 'dispositivo_compatible', 'calidad',
        ]));

        AdminActivityLogger::registrar('INVENTARIO', 'EDITAR', 'Pieza '.$inventario->nombre.' actualizada.', $inventario->sucursal_id, $inventario);

        return redirect()->route('inventario.index')->with('success', 'Pieza actualizada correctamente.');
    }

    public function destroy(Inventario $inventario)
    {
        $sucursalActivaId = session('sucursal_id') ?: auth()->user()?->sucursal_id;
        abort_unless((int) $inventario->sucursal_id === (int) $sucursalActivaId, 404);
        AdminActivityLogger::registrar('INVENTARIO', 'ELIMINAR', 'Pieza '.$inventario->nombre.' eliminada.', $inventario->sucursal_id, $inventario);
        $inventario->delete();

        return redirect()->route('inventario.index')->with('success', 'Pieza eliminada.');
    }

    /**
     * Busca una categoría por nombre (ignorando mayúsculas/minúsculas y
     * espacios sobrantes) y, si no existe ninguna variante, la crea.
     *
     * ¿Por qué así y no con firstOrCreate() directo?
     * Porque tu columna `nombre` en categorias no tiene un índice único
     * declarado, así que "Telefono", "telefono" y "TELEFONO " se tratarían
     * como registros distintos si comparamos el texto tal cual. Con
     * whereRaw + LOWER() nos aseguramos de que todas esas variantes
     * apunten siempre a la misma categoría.
     */
    private function sincronizarCategoria(string $nombreCategoria): void
    {
        // Quitamos espacios al inicio/final que el usuario pudo haber escrito
        $nombreCategoria = trim($nombreCategoria);

        // Buscamos si ya existe una categoría con ese nombre, sin importar
        // mayúsculas/minúsculas
        $categoria = Categoria::whereRaw('LOWER(nombre) = ?', [strtolower($nombreCategoria)])->first();

        // Si no existe ninguna coincidencia, la creamos con una
        // descripción por defecto (recuerda que 'descripcion' es NOT NULL
        // en tu base de datos, así que no puede ir vacía)
        if (! $categoria) {
            Categoria::create([
                'nombre' => $nombreCategoria,
                'descripcion' => 'Categoría creada automáticamente desde Inventario',
            ]);
        }
        // Si ya existía, no hacemos nada: se reutiliza tal cual está,
        // evitando duplicados.
    }
}
