<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'cotizacion_id', 'user_id', 'metodo', 'monto', 'referencia',
        'comprobante', 'notas', 'fecha_vencimiento',
        'estado', 'aprobado_por_id', 'aprobado_at', 'rechazo_motivo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'aprobado_at' => 'datetime',
    ];

    public const METODOS = ['efectivo', 'transferencia', 'tarjeta', 'credito'];
    public const ESTADOS = ['pendiente', 'aprobado', 'rechazado'];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por_id');
    }

    public function metodoLabel(): string
    {
        return match ($this->metodo) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'tarjeta' => 'Tarjeta',
            'credito' => 'Crédito (venta a plazo)',
            default => ucfirst($this->metodo),
        };
    }

    /** ¿Es un crédito vencido? (solo aplica si method=credito y ya hay fecha_vencimiento). */
    public function estaVencido(): bool
    {
        return $this->metodo === 'credito'
            && $this->fecha_vencimiento
            && $this->fecha_vencimiento->isPast()
            && $this->estado === 'aprobado';
    }

    /** Días restantes para vencer (negativo si ya venció). Null si no hay fecha. */
    public function diasParaVencer(): ?int
    {
        if (! $this->fecha_vencimiento) return null;
        return (int) round(now()->startOfDay()->diffInDays($this->fecha_vencimiento->startOfDay(), false));
    }

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            'pendiente' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'aprobado' => 'bg-green-100 text-green-700 border-green-200',
            'rechazado' => 'bg-red-100 text-red-600 border-red-200',
            default => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}
