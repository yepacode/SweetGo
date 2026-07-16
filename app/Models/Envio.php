<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Envio extends Model
{
    protected $table = 'envios';

    protected $fillable = [
        'cotizacion_id', 'zona_envio_id', 'cliente_sucursal_id',
        'direccion', 'ciudad', 'contacto', 'telefono',
        'peso_kg', 'costo', 'transportador', 'guia_numero',
        'estado', 'fecha_estimada', 'entregado_at', 'notas',
    ];

    protected $casts = [
        'peso_kg' => 'decimal:3',
        'costo' => 'decimal:2',
        'fecha_estimada' => 'date',
        'entregado_at' => 'datetime',
    ];

    public const ESTADOS = ['pendiente', 'en_ruta', 'entregado', 'cancelado'];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function zona()
    {
        return $this->belongsTo(ZonaEnvio::class, 'zona_envio_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(ClienteSucursal::class, 'cliente_sucursal_id');
    }

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            'pendiente' => 'bg-gray-100 text-gray-600',
            'en_ruta' => 'bg-sweetgo-turquoise-light text-teal-700',
            'entregado' => 'bg-green-100 text-green-700',
            'cancelado' => 'bg-red-100 text-red-600',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'en_ruta' => 'En ruta',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            default => ucfirst($this->estado),
        };
    }
}
