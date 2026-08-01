<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCatalogEntry extends Model
{
    /**
     * Estos campos representan una combinación aprendida desde Nueva Orden de Servicio.
     *
     * @var list<string>
     */
    protected $fillable = [
        'device_type',
        'brand',
        'model',
    ];
}
