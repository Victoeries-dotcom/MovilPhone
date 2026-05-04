<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventario::with('sucursal');

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        }
        if ($request->bajo_stock) {
            $query->whereColumn('cantidad_disponible', '<=', 'stock_minimo');
        }

        $inventario = $query->latest()->get();
        $sucursales = Sucursal::all();
        $stats = [
            'total'  => Inventario::count(),
            'bajo'   => Inventario::whereColumn('cantidad_disponible', '<=', 'stock_minimo')->count(),
            'valor'  => Inventario::selectRaw('SUM(cantidad_disponible * precio_costo) as total')->value('total') ?? 0,
        ];

        return view('inventario.index', compact('inventario', 'sucursales', 'stats'));
    }

    public function create()
    {
        $sucursales = Sucursal::all();
        return view('inventario.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'               => 'required|string',
            'categoria'            => 'required|string',
            'sucursal_id'          => 'required|exists:sucursales,id',
            'cantidad_disponible'  => 'required|integer|min:0',
            'stock_minimo'         => 'required|integer|min:0',
            'precio_costo'         => 'required|numeric|min:0',
            'precio_venta'         => 'required|numeric|min:0',
        ]);

        Inventario::create($request->only([
            'nombre', 'categoria', 'sucursal_id',
            'cantidad_disponible', 'stock_minimo',
            'precio_costo', 'precio_venta',
            'proveedor', 'dispositivo_compatible', 'calidad'
        ]));

        return redirect()->route('inventario.index')->with('success', 'Pieza agregada correctamente.');
    }

    public function show(Inventario $inventario)
    {
        return view('inventario.show', compact('inventario'));
    }

    public function edit(Inventario $inventario)
    {
        $sucursales = Sucursal::all();
        return view('inventario.edit', compact('inventario', 'sucursales'));
    }

    public function update(Request $request, Inventario $inventario)
    {
        $request->validate([
            'nombre'               => 'required|string',
            'categoria'            => 'required|string',
            'sucursal_id'          => 'required|exists:sucursales,id',
            'cantidad_disponible'  => 'required|integer|min:0',
            'stock_minimo'         => 'required|integer|min:0',
            'precio_costo'         => 'required|numeric|min:0',
            'precio_venta'         => 'required|numeric|min:0',
        ]);

        $inventario->update($request->only([
            'nombre', 'categoria', 'sucursal_id',
            'cantidad_disponible', 'stock_minimo',
            'precio_costo', 'precio_venta',
            'proveedor', 'dispositivo_compatible', 'calidad'
        ]));

        return redirect()->route('inventario.index')->with('success', 'Pieza actualizada correctamente.');
    }

    public function destroy(Inventario $inventario)
    {
        $inventario->delete();
        return redirect()->route('inventario.index')->with('success', 'Pieza eliminada.');
    }
}