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

    public const ESTADOS = ['borrador', 'enviada', 'pendiente_revision_pago', 'aprobada', 'rechazada', 'pagada', 'credito'];

    /** Estados en los que un vendedor puede editar su propia cotización (sin implicar reponer stock). */
    public const EDITABLES = ['borrador'];

    /** Estados en los que un admin puede editar (todos menos rechazada). Ajusta stock y estado automáticamente. */
    public const EDITABLES_ADMIN = ['borrador', 'enviada', 'pendiente_revision_pago', 'aprobada', 'pagada', 'credito'];

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

    /** Suma de pagos APROBADOS excluyendo créditos (solo dinero real: efectivo, transferencia, tarjeta). */
    public function montoPagadoAprobadoSinCredito(): string
    {
        return (string) $this->pagos()
            ->where('estado', 'aprobado')
            ->where('metodo', '!=', 'credito')
            ->sum('monto');
    }

    /** ¿Los pagos aprobados NO-crédito cubren el total? (para pasar a estado `pagada`). */
    public function pagosNoCreditoAprobadosCubrenTotal(): bool
    {
        return bccomp($this->montoPagadoAprobadoSinCredito(), (string) $this->total, 2) >= 0;
    }

    /** Saldo del crédito: total menos abonos reales (efectivo, transferencia, tarjeta) aprobados. */
    public function saldoCredito(): float
    {
        return max(0, (float) $this->total - (float) $this->montoPagadoAprobadoSinCredito());
    }

    /** Suma de abonos reales aplicados al crédito (para mostrar historial). */
    public function totalAbonado(): float
    {
        return (float) $this->montoPagadoAprobadoSinCredito();
    }

    /** Fecha de vencimiento más próxima entre los pagos crédito aprobados. Null si no hay. */
    public function proximoVencimientoCredito()
    {
        $fecha = $this->pagos()
            ->where('metodo', 'credito')
            ->where('estado', 'aprobado')
            ->whereNotNull('fecha_vencimiento')
            ->orderBy('fecha_vencimiento')
            ->value('fecha_vencimiento');
        return $fecha ? \Carbon\Carbon::parse($fecha) : null;
    }

    /**
     * Aging del crédito: 'vencido' | 'por_vencer' (≤7 días) | 'al_dia' | 'sin_plazo'.
     * Se calcula sobre proximoVencimientoCredito(). Solo aplica si estado=credito con saldo > 0.
     */
    public function agingCredito(): string
    {
        $v = $this->proximoVencimientoCredito();
        if (! $v) return 'sin_plazo';
        $dias = (int) round(now()->startOfDay()->diffInDays($v->startOfDay(), false));
        if ($dias < 0) return 'vencido';
        if ($dias <= 7) return 'por_vencer';
        return 'al_dia';
    }

    /** Días para vencer (negativo si venció). Null si sin_plazo. */
    public function diasVencimientoCredito(): ?int
    {
        $v = $this->proximoVencimientoCredito();
        if (! $v) return null;
        return (int) round(now()->startOfDay()->diffInDays($v->startOfDay(), false));
    }

    public function scopeEnCredito($query)
    {
        return $query->where('estado', 'credito');
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

    /**
     * ¿Este usuario puede editar la cotización?
     * - Admin: puede editar en cualquier estado excepto `rechazada`. Al guardar, el sistema
     *   ajusta el stock automáticamente si ya estaba descontado y recalcula el estado según
     *   los pagos vs el nuevo total.
     * - Vendedor: solo si es SU propia cotización y está en `borrador`.
     * Reglas: "editar antes de enviar" (vendedor) + "editar en cualquier momento si el cliente
     * pide más/menos" (admin, jul-2026).
     */
    public function puedeEditar(?\App\Models\User $user): bool
    {
        if (! $user) return false;

        if ($user->hasRole('admin')) {
            return in_array($this->estado, self::EDITABLES_ADMIN, true);
        }

        // Vendedor: solo su propio borrador y sin pagos comprometidos.
        return $this->user_id === $user->id && $this->esEditable();
    }

    /**
     * Ajusta el stock según el delta entre los items previos y los nuevos.
     * Solo se ejecuta cuando la cotización tenía stock_descontado=true (ya se había aprobado).
     * Devuelve una lista de mensajes descriptivos por movimiento (para bitácora / warnings).
     *
     * $itemsAntes: array de arrays [producto_id, cantidad] tomados ANTES del update.
     * $itemsDespues: array de arrays [producto_id, cantidad] con los items recién guardados.
     */
    public function ajustarStockPorEdicion(array $itemsAntes, array $itemsDespues): array
    {
        if (! $this->stock_descontado) {
            return []; // aún no había impactado stock, los items nuevos lo harán al aprobar
        }

        $antes = collect($itemsAntes)->groupBy('producto_id')->map(fn ($grp) => (int) $grp->sum('cantidad'));
        $despues = collect($itemsDespues)->groupBy('producto_id')->map(fn ($grp) => (int) $grp->sum('cantidad'));

        $ids = $antes->keys()->merge($despues->keys())->unique();
        $movimientos = [];

        foreach ($ids as $pid) {
            if (! $pid) continue;
            $delta = (int) $despues->get($pid, 0) - (int) $antes->get($pid, 0);
            if ($delta === 0) continue;

            $producto = \App\Models\Producto::find($pid);
            if (! $producto) continue;

            if ($delta > 0) {
                // Aumentó la cantidad → salida adicional de stock
                $producto->registrarMovimiento('salida', $delta, "Ajuste por edición de {$this->numero}", $this->numero);
                $movimientos[] = "−{$delta} «{$producto->nombre}»";
            } else {
                // Bajó la cantidad → repone stock
                $producto->registrarMovimiento('entrada', abs($delta), "Reposición por edición de {$this->numero}", $this->numero);
                $movimientos[] = "+".abs($delta)." «{$producto->nombre}»";
            }
        }

        return $movimientos;
    }

    /**
     * Recalcula el estado después de una edición admin, según pagos vs nuevo total.
     * Devuelve el estado anterior (para diff / warning) o null si no cambia.
     */
    public function recalcularEstadoPorEdicion(): ?string
    {
        $anterior = $this->estado;
        if (in_array($anterior, ['borrador', 'rechazada'], true)) {
            return null; // borradores no cambian; rechazadas no se editan
        }

        $totalHoy = (float) $this->total;
        $noCredAprobado = (float) $this->montoPagadoAprobadoSinCredito();
        $todoAprobado = (float) $this->montoPagadoAprobado();
        $pendiente = (float) $this->pagos()->where('estado', 'pendiente')->sum('monto');

        if ($noCredAprobado + 0.01 >= $totalHoy) {
            $nuevo = 'pagada';
        } elseif ($todoAprobado + 0.01 >= $totalHoy) {
            $nuevo = 'credito';
        } elseif ($pendiente > 0) {
            $nuevo = 'pendiente_revision_pago';
        } else {
            // Ya había sido enviada/aprobada/etc., la mantenemos como 'enviada' (no volvemos a borrador).
            $nuevo = 'enviada';
        }

        if ($nuevo !== $anterior) {
            $this->update(['estado' => $nuevo]);
            return $anterior;
        }
        return null;
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
            'credito'                  => 'bg-amber-100 text-amber-700',
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
            'credito'                  => 'A crédito',
            'rechazada'                => 'Rechazada',
            default                    => ucfirst($this->estado),
        };
    }
}
