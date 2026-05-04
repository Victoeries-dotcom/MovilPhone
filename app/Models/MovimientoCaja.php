<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    protected $fillable = [
        'sucursal_id',
        'tipo',
        'categoria',
        'monto',
        'descripcion',
        'os_id',
        'user_id'
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function orden()
    {
        return $this->belongsTo(OrdenServicio::class, 'os_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}