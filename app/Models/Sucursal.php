<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';

   protected $fillable = [
    'nombre',
    'ubicacion',
    'nombre_encargado',
    'telefono_encargado',
    'horario',
        'ubicacion_url',
];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function ordenesServicio()
    {
        return $this->hasMany(OrdenServicio::class);
    }

    public function movimientosCaja()
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class);
    }

    /**
     * Conecta la sucursal con sus usuarios asignados.
     * Sirve para listar técnicos y usuarios según la sucursal activa.
     */
    public function usuarios()
    {
        return $this->hasMany(User::class);
    }
}
