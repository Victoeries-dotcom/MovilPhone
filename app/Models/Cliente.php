<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'telefono_principal',
        'telefono_alternativo',
        'direccion',
        'sucursal_habitual_id'
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_habitual_id');
    }

    public function ordenes()
    {
        return $this->hasMany(OrdenServicio::class, 'cliente_id');
    }
}