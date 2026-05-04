<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstado extends Model
{
    protected $fillable = [
        'os_id',
        'estado',
        'nota'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenServicio::class, 'os_id');
    }
}