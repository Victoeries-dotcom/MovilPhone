<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';
    
    protected $fillable = ['nombre'];

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'sucursal_habitual_id');
    }

    public function ordenes()
    {
        return $this->hasMany(OrdenServicio::class);
    }

    public function inventario()
    {
        return $this->hasMany(Inventario::class);
    }

    public function movimientosCaja()
    {
        return $this->hasMany(MovimientoCaja::class);
    }
}