<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventario';

    protected $fillable = [
        'nombre',
        'categoria',
        'sucursal_id',
        'cantidad_disponible',
        'stock_minimo',
        'precio_costo',
        'precio_venta',
        'proveedor',
        'dispositivo_compatible',
        'calidad'
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function bajoStock(): bool
    {
        return $this->cantidad_disponible <= $this->stock_minimo;
    }
}