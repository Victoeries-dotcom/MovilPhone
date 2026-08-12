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
        'sucursal_habitual_id',
    ];

    /**
     * Genera una versión comparable del teléfono para facilitar búsquedas.
     * Se conecta con clientes.telefono_normalizado y elimina espacios,
     * guiones o paréntesis sin convertir el teléfono en la identidad del cliente.
     */
    public static function normalizarTelefono(?string $telefono): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $telefono ?? '') ?? '');
    }

    /**
     * Mantiene telefono_normalizado actualizado en cualquier alta o edición.
     * Los registros continúan separados por clientes.id aunque compartan el número.
     */
    protected static function booted(): void
    {
        static::saving(function (Cliente $cliente) {
            // Sin teléfono conserva NULL para permitir varios clientes anónimos sin compartir una llave vacía.
            $telefonoNormalizado = static::normalizarTelefono($cliente->telefono_principal);
            $cliente->telefono_normalizado = $telefonoNormalizado !== '' ? $telefonoNormalizado : null;
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

    /**
     * Relaciona el historial comercial del cliente con ventas.cliente_id.
     * Se utiliza para confirmar sus compras anteriores al registrar una nueva venta.
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
