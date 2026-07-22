<?php

use App\Http\Controllers\AdminActivityController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// La pagina offline se conecta con el service worker y permanece disponible sin autenticacion.
Route::view('/offline', 'offline')->name('offline');

Route::middleware('auth')->group(function () {
    /* Panel y utilidades globales: todos los roles autenticados conservan acceso. */
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    // Alias de compatibilidad: conecta Laravel Breeze con el mismo panel principal profesional.
    Route::redirect('/dashboard', '/')->name('dashboard');
    Route::get('buscar', [GlobalSearchController::class, 'index'])->name('buscar.global');
    Route::get('buscar/{tipo}/{id}', [GlobalSearchController::class, 'quickView'])->name('buscar.detalle');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* Clientes se comparte entre taller y ventas; el middleware verifica users.rol en el backend. */
    Route::middleware('role:superusuario,usuario,tecnico,vendedor')->group(function () {
        Route::resource('clientes', ClienteController::class);
    });

    /* Ordenes pertenece al equipo de taller y conserva entrega, rechazo, ticket y sticker. */
    Route::middleware('role:superusuario,usuario,tecnico')->group(function () {
        Route::get('ordenes/cliente-por-telefono', [OrdenServicioController::class, 'buscarClientePorTelefono'])
            ->name('ordenes.buscarClientePorTelefono');
        Route::resource('ordenes', OrdenServicioController::class)
            ->parameters(['ordenes' => 'ordenServicio']);
        Route::post('ordenes/{ordenServicio}/estado', [OrdenServicioController::class, 'avanzarEstado'])
            ->name('ordenes.avanzarEstado');
        Route::post('ordenes/{ordenServicio}/entregar', [OrdenServicioController::class, 'entregar'])
            ->name('ordenes.entregar');
        Route::post('ordenes/{ordenServicio}/rechazar', [OrdenServicioController::class, 'rechazar'])
            ->name('ordenes.rechazar');
        Route::get('ordenes/{ordenServicio}/ticket-entrega', [OrdenServicioController::class, 'ticketEntrega'])
            ->name('ordenes.ticketEntrega');
        Route::get('ordenes/{ordenServicio}/sticker', [OrdenServicioController::class, 'sticker'])
            ->name('ordenes.sticker');
    });

    /* Inventario y categorias quedan protegidos para captura y administracion. */
    Route::middleware('role:superusuario,capturista,usuario')->group(function () {
        Route::resource('inventario', InventarioController::class);
        Route::resource('categorias', CategoriaController::class);
    });

    /* Corte se declara antes del resource para que "corte" no se interprete como un ID. */
    Route::middleware('role:superusuario')->group(function () {
        Route::get('caja/corte', [MovimientoCajaController::class, 'corteCaja'])->name('caja.corte');
        Route::post('caja/hora-corte', [MovimientoCajaController::class, 'guardarHoraCorte'])->name('caja.horaCorte');
    });

    /* Caja se conecta con usuarios del taller y mantiene el corte exclusivo para admin. */
    Route::middleware('role:superusuario,usuario,tecnico')->group(function () {
        Route::post('caja/egreso-rapido', [MovimientoCajaController::class, 'registrarEgreso'])->name('caja.egreso');
        Route::post('caja/ingreso-rapido', [MovimientoCajaController::class, 'registrarIngreso'])->name('caja.ingreso');
        Route::get('caja/{movimientoCaja}/ticket', [MovimientoCajaController::class, 'ticket'])->name('caja.ticket');
        Route::resource('caja', MovimientoCajaController::class)
            ->parameters(['caja' => 'movimientoCaja']);
    });

    /* Ventas se conecta solo con vendedores, superusuarios y el rol "usuario". */
    Route::middleware('role:superusuario,vendedor,usuario')->group(function () {
        Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    });

    /* Administracion avanzada: configuracion, usuarios, reportes, actividad y respaldos. */
    Route::middleware('role:superusuario')->group(function () {
        Route::resource('usuarios', UsuarioController::class);
        Route::post('sucursales/cambiar', [SucursalController::class, 'cambiar'])->name('sucursales.cambiar');
        Route::resource('sucursales', SucursalController::class)
            ->only(['index', 'create', 'store', 'destroy'])
            ->parameters(['sucursales' => 'sucursal']);

        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');

        Route::get('actividad', [AdminActivityController::class, 'index'])->name('actividad.index');
        Route::get('actividad/ultimas', [AdminActivityController::class, 'latest'])->name('actividad.latest');
        Route::get('actividad/notificaciones', [AdminActivityController::class, 'notifications'])->name('actividad.notificaciones');
        Route::post('actividad/notificaciones/leidas', [AdminActivityController::class, 'markNotificationsRead'])->name('actividad.notificaciones.leidas');

        Route::get('configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
        Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        Route::get('configuracion/garantia', [ConfiguracionController::class, 'editarGarantia'])->name('configuracion.garantia');
        Route::post('configuracion/garantia', [ConfiguracionController::class, 'guardarGarantia'])->name('configuracion.garantia.guardar');

        Route::get('configuracion/respaldos', [BackupController::class, 'index'])->name('respaldos.index');
        Route::post('configuracion/respaldos', [BackupController::class, 'store'])->name('respaldos.store');
        Route::get('configuracion/respaldos/{archivo}', [BackupController::class, 'download'])->name('respaldos.download');
        Route::delete('configuracion/respaldos/{archivo}', [BackupController::class, 'destroy'])->name('respaldos.destroy');
    });
});