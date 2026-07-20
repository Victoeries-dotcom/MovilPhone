<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    // Estos campos conectan los formularios, las órdenes y las ventas con el registro financiero.
    protected $fillable = [
        'sucursal_id',
        'tipo',
        'categoria',
        'monto',
        'metodo_pago',
        'anticipo',
        'saldo_pendiente',
        'es_anticipo',
        'es_pago_final',
        'referencia_pago',
        'descripcion',
        'os_id',
        'user_id',
    ];

    // Los casts mantienen importes y banderas con el tipo correcto al calcular Caja y reportes.
    protected $casts = [
        'monto' => 'decimal:2',
        'anticipo' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'es_anticipo' => 'boolean',
        'es_pago_final' => 'boolean',
    ];

    /** Conecta cada movimiento con la sucursal donde se recibió o gastó el dinero. */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    /** Conecta el cobro con su orden de servicio para mostrar el folio y abrir su detalle. */
    public function orden()
    {
        return $this->belongsTo(OrdenServicio::class, 'os_id');
    }

    /** Conecta el movimiento con el usuario que realizó el registro. */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
