<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    // Le dice a Laravel que este modelo usa la tabla 'categorias'
    protected $table = 'categorias';

    // Campos que se pueden llenar de forma masiva (create/update)
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // Relación existente: cuenta/lista las Órdenes de Servicio
    // que tengan el mismo texto en 'categoria' que el 'nombre' de esta categoría
    public function ordenes()
    {
        return $this->hasMany(OrdenServicio::class, 'categoria', 'nombre');
    }

    // NUEVA relación: mismo principio, pero apuntando a Inventario.
    // Sirve para saber cuántos productos de inventario pertenecen a esta categoría.
    public function productos()
    {
        return $this->hasMany(Inventario::class, 'categoria', 'nombre');
    }
}