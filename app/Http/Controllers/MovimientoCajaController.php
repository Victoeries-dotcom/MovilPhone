<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCaja;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class MovimientoCajaController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoCaja::with('sucursal');

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        }
        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->fecha) {
            $query->whereDate('created_at', $request->fecha);
        }

        $movimientos = $query->latest()->get();
        $sucursales = Sucursal::all();

        $hoy = now()->toDateString();
        $stats = [
            'ingresos' => MovimientoCaja::where('tipo', 'INGRESO')->whereDate('created_at', $hoy)->sum('monto'),
            'egresos'  => MovimientoCaja::where('tipo', 'EGRESO')->whereDate('created_at', $hoy)->sum('monto'),
        ];
        $stats['balance'] = $stats['ingresos'] - $stats['egresos'];

        return view('caja.index', compact('movimientos', 'sucursales', 'stats'));
    }

    public function create()
    {
        $sucursales = Sucursal::all();
        return view('caja.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'tipo'        => 'required|in:INGRESO,EGRESO',
            'categoria'   => 'required|string',
            'monto'       => 'required|numeric|min:0',
        ]);

        MovimientoCaja::create($request->only([
            'sucursal_id', 'tipo', 'categoria',
            'monto', 'descripcion', 'os_id', 'user_id'
        ]));

        return redirect()->route('caja.index')->with('success', 'Movimiento registrado correctamente.');
    }

    public function destroy(MovimientoCaja $movimientoCaja)
    {
        $movimientoCaja->delete();
        return redirect()->route('caja.index')->with('success', 'Movimiento eliminado.');
    }

    public function show(MovimientoCaja $movimientoCaja) {}
    public function edit(MovimientoCaja $movimientoCaja) {}
    public function update(Request $request, MovimientoCaja $movimientoCaja) {}
}