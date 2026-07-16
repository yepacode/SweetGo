<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Producto extends Model
{
    use RegistraBitacora;

    protected array $bitacoraIgnorar = ['stock_actual']; // el stock se audita vía movimientos

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id', 'nombre', 'referencia', 'descripcion',
        'precio', 'imagen', 'stock_actual', 'stock_minimo', 'stock_maximo', 'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'stock_maximo' => 'integer',
        'activo' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function cotizacionItems()
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function preciosProducto()
    {
        return $this->hasMany(PrecioProducto::class);
    }

    /**
     * Precio del producto en una lista dada.
     * Si no hay precio específico en esa lista, cae al precio base (productos.precio).
     */
    public function precioEnLista($listaId): float
    {
        if (! $listaId) {
            return (float) $this->precio;
        }

        $precio = $this->preciosProducto->firstWhere('lista_precio_id', (int) $listaId);

        return (float) ($precio?->precio ?? $this->precio);
    }

    /** ¿Está en o por debajo del stock mínimo? */
    public function getStockBajoAttribute(): bool
    {
        return $this->stock_minimo > 0 && $this->stock_actual <= $this->stock_minimo;
    }

    /**
     * Registra un movimiento de stock y actualiza el stock actual de forma atómica.
     *
     * @param  string  $tipo      entrada|salida|ajuste
     * @param  int     $cantidad  cantidad del movimiento (para 'ajuste' es el stock final deseado)
     */
    public function registrarMovimiento(string $tipo, int $cantidad, ?string $motivo = null, ?string $referencia = null): MovimientoStock
    {
        return DB::transaction(function () use ($tipo, $cantidad, $motivo, $referencia) {
            /** @var Producto $producto */
            $producto = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $anterior = $producto->stock_actual;

            $nuevo = match ($tipo) {
                'entrada' => $anterior + abs($cantidad),
                'salida'  => $anterior - abs($cantidad),
                'ajuste'  => $cantidad, // en ajuste, la cantidad ES el nuevo stock
                default   => throw new \InvalidArgumentException("Tipo de movimiento inválido: {$tipo}"),
            };

            if ($nuevo < 0) {
                throw new \RuntimeException("Stock insuficiente: {$producto->nombre} tiene {$anterior} unidades.");
            }

            $producto->stock_actual = $nuevo;
            $producto->save();

            $movimiento = $producto->movimientos()->create([
                'tipo' => $tipo,
                'cantidad' => $tipo === 'ajuste' ? abs($nuevo - $anterior) : abs($cantidad),
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevo,
                'motivo' => $motivo,
                'referencia' => $referencia,
                'user_id' => Auth::id(),
            ]);

            if (Auth::check()) {
                Bitacora::registrar(
                    'movimiento',
                    "Registró {$tipo} de {$movimiento->cantidad} en «{$producto->nombre}» (stock {$anterior}→{$nuevo})",
                    'MovimientoStock',
                    $movimiento->id
                );
            }

            $this->stock_actual = $nuevo;

            return $movimiento;
        });
    }
}
