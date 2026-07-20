<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivity extends Model
{
    protected $table = 'admin_activities';

    protected $fillable = [
        'modulo',
        'accion',
        'descripcion',
        'sucursal_id',
        'user_id',
        'registro_tipo',
        'registro_id',
    ];

    /**
     * Conecta la actividad con la sucursal donde ocurrió el movimiento.
     * Sirve para que el admin pueda identificar de qué sucursal viene cada captura.
     */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Conecta la actividad con el usuario que hizo la acción.
     * Sirve para mostrar al admin quién registró, actualizó o eliminó información.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
