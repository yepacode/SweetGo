<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cotizacion extends Model
{
    use RegistraBitacora;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'numero', 'cliente_id', 'user_id', 'estado', 'fecha', 'validez',
        'subtotal', 'descuento', 'con_iva', 'iva_porcentaje', 'iva_monto',
        'total', 'notas', 'stock_descontado', 'aprobada_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'validez' => 'date',
        'aprobada_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'con_iva' => 'boolean',
        'iva_porcentaje' => 'decimal:2',
        'iva_monto' => 'decimal:2',
        'total' => 'decimal:2',
        'stock_descontado' => 'boolean',
    ];

    public const ESTADOS = ['borrador', 'enviada', 'pendiente_revision_pago', 'aprobada', 'rechazada', 'pagada'];

    /** Estados en los que la cotización todavía puede recibir cambios estructurales (agregar/quitar items). */
    public const EDITABLES = ['borrador', 'enviada'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(CotizacionItem::class);
    }

    /** Relación base sin ORDER BY para agregaciones limpias (sum, count). */
    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    /** Igual que pagos() pero ordenada por más reciente — útil en listados/UI. */
    public function pagosRecientes()
    {
        return $this->hasMany(Pago::class)->latest();
    }

    public function envio()
    {
        return $this->hasOne(Envio::class);
    }

    /** Suma de pagos aprobados + pendientes (para saber cuánto lleva comprometido). */
    public function montoPagado(): string
    {
        return (string) $this->pagos()->whereIn('estado', ['pendiente', 'aprobado'])->sum('monto');
    }

    /** Solo suma de pagos APROBADOS por admin. */
    public function montoPagadoAprobado(): string
    {
        return (string) $this->pagos()->where('estado', 'aprobado')->sum('monto');
    }

    /**
     * Cotización editable = está en un estado editable Y no tiene pagos activos (pendientes ni aprobados).
     * Los pagos rechazados no bloquean (se pueden volver a subir).
     */
    public function esEditable(): bool
    {
        if (! in_array($this->estado, self::EDITABLES, true)) {
            return false;
        }
        return ! $this->pagos()->whereIn('estado', ['pendiente', 'aprobado'])->exists();
    }

    /** ¿Los pagos activos cubren ya el total? Comparación decimal segura (bccomp). */
    public function estaTotalmentePagada(): bool
    {
        return bccomp($this->montoPagado(), (string) $this->total, 2) >= 0;
    }

    /** ¿Los pagos APROBADOS cubren el total? Se usa para pasar a estado "pagada". */
    public function pagosAprobadosCubrenTotal(): bool
    {
        return bccomp($this->montoPagadoAprobado(), (string) $this->total, 2) >= 0;
    }

    /**
     * Crea una cotización con número correlativo COT-0001 de forma SEGURA ante concurrencia.
     * Si dos usuarios generan el mismo número simultáneamente, la violación de unique lo
     * detecta y reintenta con el siguiente número.
     *
     * Uso: Cotizacion::crearConNumero($atributosSinNumero)
     */
    public static function crearConNumero(array $atributos): self
    {
        for ($intento = 0; $intento < 5; $intento++) {
            $ultimoNum = static::orderByDesc('id')->value('numero');
            $base = $ultimoNum ? (int) preg_replace('/\D/', '', $ultimoNum) : 0;
            $atributos['numero'] = 'COT-' . str_pad((string) ($base + 1 + $intento), 4, '0', STR_PAD_LEFT);

            try {
                return static::create($atributos);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] !== 1062) { // no es una violación de unique → propagar
                    throw $e;
                }
                // colisión: siguiente intento
            }
        }
        throw new \RuntimeException('No se pudo generar un número único de cotización tras varios intentos.');
    }

    /** Legacy: expone el próximo número previsto (útil solo para vistas). No es concurrente-seguro. */
    public static function siguienteNumero(): string
    {
        $ultimo = static::orderByDesc('id')->value('numero');
        $n = $ultimo ? ((int) preg_replace('/\D/', '', $ultimo)) + 1 : 1;

        return 'COT-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Recalcula subtotal, IVA y total a partir de los items, el descuento y el flag con_iva.
     * Fórmula: base = max(0, subtotal - descuento). IVA = base * (%/100) si con_iva. Total = base + IVA.
     */
    public function recalcularTotales(): void
    {
        $subtotal = (float) $this->items()->sum('subtotal');
        $base = max(0, $subtotal - (float) $this->descuento);
        $ivaMonto = $this->con_iva ? round($base * ((float) $this->iva_porcentaje / 100), 2) : 0;

        $this->subtotal = $subtotal;
        $this->iva_monto = $ivaMonto;
        $this->total = $base + $ivaMonto;
        $this->save();
    }

    /**
     * Aprueba la cotización y descuenta el stock de cada item (una sola vez).
     * Si algún item tiene el producto borrado, lanza excepción antes de tocar stock.
     */
    public function aprobar(): void
    {
        DB::transaction(function () {
            $cot = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($cot->stock_descontado) {
                $cot->update(['estado' => 'aprobada', 'aprobada_at' => now()]);
                return;
            }

            $items = $cot->items()->with('producto')->get();

            // Falla temprano si hay items huérfanos (producto borrado): no queremos aprobación parcial.
            $huerfanos = $items->filter(fn ($i) => ! $i->producto);
            if ($huerfanos->isNotEmpty()) {
                $nombres = $huerfanos->pluck('nombre')->implode(', ');
                throw new \RuntimeException("No se puede aprobar: los siguientes productos ya no existen y deben quitarse de la cotización: {$nombres}.");
            }

            foreach ($items as $item) {
                $item->producto->registrarMovimiento(
                    'salida', $item->cantidad,
                    "Cotización {$cot->numero} aprobada", $cot->numero
                );
            }

            $cot->update([
                'estado' => 'aprobada',
                'aprobada_at' => now(),
                'stock_descontado' => true,
            ]);

            $this->refresh();
        });
    }

    /**
     * Revierte el descuento de stock (para rechazar/anular una cotización aprobada).
     * Registra un movimiento de ENTRADA por cada item y limpia el flag `stock_descontado`.
     * Atómico e idempotente.
     */
    public function revertirStock(): void
    {
        DB::transaction(function () {
            $cot = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if (! $cot->stock_descontado) {
                return; // ya está revertido
            }

            foreach ($cot->items()->with('producto')->get() as $item) {
                if ($item->producto) {
                    $item->producto->registrarMovimiento(
                        'entrada', $item->cantidad,
                        "Reposición por {$cot->numero} (rechazada/anulada)", $cot->numero
                    );
                }
            }

            $cot->update(['stock_descontado' => false, 'aprobada_at' => null]);

            if (\Illuminate\Support\Facades\Auth::check()) {
                \App\Models\Bitacora::registrar(
                    'reversión',
                    "Revirtió el stock de la Cotización «{$cot->numero}» (anulación de aprobación)",
                    'Cotizacion', $cot->id
                );
            }

            $this->refresh();
        });
    }

    protected function bitacoraEtiqueta(): string
    {
        return 'Cotización';
    }

    /** ¿La validez ya pasó y la cotización sigue "abierta"? */
    public function getEstaVencidaAttribute(): bool
    {
        return $this->validez
            && $this->validez->isPast()
            && in_array($this->estado, ['borrador', 'enviada'], true);
    }

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            'borrador'                 => 'bg-gray-100 text-gray-600',
            'enviada'                  => 'bg-sweetgo-turquoise-light text-teal-700',
            'pendiente_revision_pago'  => 'bg-yellow-100 text-yellow-700',
            'aprobada'                 => 'bg-green-100 text-green-700',
            'pagada'                   => 'bg-emerald-100 text-emerald-700',
            'rechazada'                => 'bg-red-100 text-red-600',
            default                    => 'bg-gray-100 text-gray-600',
        };
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'borrador'                 => 'Borrador',
            'enviada'                  => 'Enviada',
            'pendiente_revision_pago'  => 'Pendiente revisión pago',
            'aprobada'                 => 'Aprobada',
            'pagada'                   => 'Pagada',
            'rechazada'                => 'Rechazada',
            default                    => ucfirst($this->estado),
        };
    }
}
