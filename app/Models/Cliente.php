<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'telefono_principal',
        'telefono_normalizado',
        'telefono_alternativo',
        'direccion',
        'sucursal_habitual_id'
    ];

    /**
     * Genera el identificador único del cliente a partir de su teléfono.
     * Se conecta con clientes.telefono_normalizado y elimina espacios,
     * guiones o paréntesis para evitar registros duplicados por formato.
     */
    public static function normalizarTelefono(?string $telefono): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $telefono ?? '') ?? '');
    }

    /**
     * Mantiene telefono_normalizado actualizado en cualquier alta o edición.
     * Se conecta con Clientes, Ventas y Órdenes de Servicio.
     */
    protected static function booted(): void
    {
        static::saving(function (Cliente $cliente) {
            $cliente->telefono_normalizado = static::normalizarTelefono($cliente->telefono_principal);
        });
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_habitual_id');
    }

    public function ordenes()
    {
        return $this->hasMany(OrdenServicio::class, 'cliente_id');
    }
}
