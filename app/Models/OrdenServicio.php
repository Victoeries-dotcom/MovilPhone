<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenServicio extends Model
{
    protected $table = 'ordenes_servicio';

    // Catálogo único del panel principal: alimenta el selector y valida ordenes_servicio.estado.
    public const ESTADOS = [
        'RECIBIDO' => 'En espera',
        'EN DIAGNÓSTICO' => 'Diagnóstico',
        'EN REPARACIÓN' => 'Reparación',
        'TERMINADO' => 'Listo para recoger',
        'ENTREGADO' => 'Entregado',
        'RECHAZADO' => 'No quedó / Rechazado',
        'GARANTÍA' => 'Garantía',
    ];

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
        // Conserva el método usado al liquidar, que puede ser distinto al método del anticipo.
        'metodo_pago_final',
        // Conserva el dinero recibido al entregar sin sobrescribir el precio diagnosticado.
        'pago_final',
        'presupuesto_total',
        'mano_obra',
        'fecha_entrega_estimada',
        'fecha_entrega_real',
        'os_origen_id',
    ];

    const TRANSICIONES = [
        'RECIBIDO' => ['EN DIAGNÓSTICO'],
        'EN DIAGNÓSTICO' => ['ESPERANDO AUTORIZACIÓN'],
        'ESPERANDO AUTORIZACIÓN' => ['AUTORIZADO', 'RECHAZADO'],
        'AUTORIZADO' => ['EN REPARACIÓN'],
        'RECHAZADO' => [],
        'EN REPARACIÓN' => ['ESPERANDO REFACCIÓN', 'TERMINADO'],
        'ESPERANDO REFACCIÓN' => ['EN REPARACIÓN'],
        'TERMINADO' => ['NOTIFICADO', 'ENTREGADO'],
        'NOTIFICADO' => ['ENTREGADO'],
        'ENTREGADO' => ['GARANTÍA'],
        'GARANTÍA' => [],
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

    /**
     * Devuelve el precio autorizado: usa presupuesto_total y, si falta, el diagnóstico monetario.
     */
    public function precioServicio(): float
    {
        $presupuesto = (float) ($this->presupuesto_total ?? 0);

        return max(0, $presupuesto > 0 ? $presupuesto : (float) ($this->cobro_diagnostico ?? 0));
    }

    /**
     * Calcula únicamente dinero pendiente; diagnóstico es precio y no se cuenta como pago recibido.
     */
    public function saldoPendiente(): float
    {
        return max(
            0,
            $this->precioServicio() - (float) ($this->anticipo ?? 0) - (float) ($this->pago_final ?? 0)
        );
    }

    public function puedeAvanzarA(string $nuevoEstado): bool
    {
        $permitidos = self::TRANSICIONES[$this->estado] ?? [];

        return in_array($nuevoEstado, $permitidos);
    }
}
