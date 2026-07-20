<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenServicio extends Model
{
    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'numero_os',
        'cliente_id',
        // Guarda el teléfono extra capturado en Nueva OS para avisos adicionales al cliente.
        'cliente_telefono_extra',
        'sucursal_id',
        'tecnico_id',
        'estado',
        'marca',
        'modelo',
        // Guarda el tipo de dispositivo capturado en Nueva OS y se muestra en el detalle de la orden.
        'tipo_dispositivo',
        'imei',
        'problema_reportado',
        'problema_diagnosticado',
        'accesorios_entregados',
        'estado_fisico',
        // Permite guardar el patrón, PIN o contraseña del dispositivo desde el formulario de órdenes.
        'contrasena_dispositivo',
        'cobro_diagnostico',
        // Guarda el anticipo recibido al crear la OS y se conecta con reportes/caja si luego se usa para cobros.
        'anticipo',
        'metodo_pago_anticipo',
        'presupuesto_total',
        'mano_obra',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'os_origen_id'
    ];

    const TRANSICIONES = [
        'RECIBIDO'                 => ['EN DIAGNÓSTICO'],
        'EN DIAGNÓSTICO'           => ['ESPERANDO AUTORIZACIÓN'],
        'ESPERANDO AUTORIZACIÓN'   => ['AUTORIZADO', 'RECHAZADO'],
        'AUTORIZADO'               => ['EN REPARACIÓN'],
        'RECHAZADO'                => [],
        'EN REPARACIÓN'            => ['ESPERANDO REFACCIÓN', 'TERMINADO'],
        'ESPERANDO REFACCIÓN'      => ['EN REPARACIÓN'],
        'TERMINADO'                => ['NOTIFICADO', 'ENTREGADO'],
        'NOTIFICADO'               => ['ENTREGADO'],
        'ENTREGADO'                => ['GARANTÍA'],
        'GARANTÍA'                 => [],
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstado::class, 'os_id');
    }

    public function movimientosCaja()
    {
        return $this->hasMany(MovimientoCaja::class, 'os_id');
    }

    public function osOrigen()
    {
        return $this->belongsTo(OrdenServicio::class, 'os_origen_id');
    }

    public function puedeAvanzarA(string $nuevoEstado): bool
    {
        $permitidos = self::TRANSICIONES[$this->estado] ?? [];
        return in_array($nuevoEstado, $permitidos);
    }
}
