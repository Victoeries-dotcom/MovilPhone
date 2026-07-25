<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'calidad',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function bajoStock(): bool
    {
        return $this->cantidad_disponible <= $this->stock_minimo;
    }

    /**
     * Resume el inventario actual por proveedor para el reporte administrativo.
     * Se conecta con cantidad_disponible y precio_costo; por eso cada alta,
     * venta o edición del inventario actualiza automáticamente el valor costo.
     */
    public function scopeResumenPorProveedor(Builder $query): Builder
    {
        $proveedorNormalizado = "COALESCE(NULLIF(TRIM(proveedor), ''), 'Sin proveedor')";

        return $query
            ->selectRaw($proveedorNormalizado.' as proveedor')
            ->selectRaw('COUNT(*) as productos')
            ->selectRaw('SUM(CASE WHEN cantidad_disponible > 0 THEN cantidad_disponible ELSE 0 END) as existencia')
            ->selectRaw('SUM(CASE WHEN cantidad_disponible > 0 THEN cantidad_disponible * COALESCE(precio_costo, 0) ELSE 0 END) as valor_costo')
            ->groupByRaw($proveedorNormalizado);
    }
}
