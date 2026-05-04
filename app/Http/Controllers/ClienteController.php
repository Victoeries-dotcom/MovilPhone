<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::with('sucursal')->withCount('ordenes')
                        ->withSum('ordenes', 'presupuesto_total');

        if ($request->search) {
            $query->where('nombre', 'like', '%'.$request->search.'%')
                  ->orWhere('telefono_principal', 'like', '%'.$request->search.'%');
        }

        $clientes = $query->latest()->get();
        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        $sucursales = Sucursal::all();
        return view('clientes.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'             => 'required|string',
            'telefono_principal' => 'required|string|unique:clientes,telefono_principal',
        ]);

        Cliente::create($request->only([
            'nombre',
            'telefono_principal',
            'telefono_alternativo',
            'direccion',
            'sucursal_habitual_id'
        ]));

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function show(Cliente $cliente)
    {
        $cliente->load(['ordenes.sucursal', 'sucursal']);
        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        $sucursales = Sucursal::all();
        return view('clientes.edit', compact('cliente', 'sucursales'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre'             => 'required|string',
            'telefono_principal' => 'required|string|unique:clientes,telefono_principal,'.$cliente->id,
        ]);

        $cliente->update($request->only([
            'nombre',
            'telefono_principal',
            'telefono_alternativo',
            'direccion',
            'sucursal_habitual_id'
        ]));

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }
}