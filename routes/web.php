<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientoCajaController;

// Página principal redirige a órdenes
Route::get('/', function () {
    return redirect()->route('ordenes.index');
});

// Órdenes de Servicio
Route::resource('ordenes', OrdenServicioController::class);
Route::post('ordenes/{ordenServicio}/estado', [OrdenServicioController::class, 'avanzarEstado'])
     ->name('ordenes.estado');

// Clientes
Route::resource('clientes', ClienteController::class);

// Inventario
Route::resource('inventario', InventarioController::class);

// Caja
Route::resource('caja', MovimientoCajaController::class)->except(['show', 'edit', 'update']);