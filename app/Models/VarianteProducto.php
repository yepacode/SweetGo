<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Variante de un producto (color, talla, presentación…).
 * Cada variante lleva su propio stock y sus propios precios por lista.
 * La referencia es única global (el cliente la escribe).
 */
class VarianteProducto extends Model
{
    protected $table = 'variantes_producto';

    protected $fillable = [
        'producto_id', 'nombre', 'referencia',
        'stock_actual', 'stock_minimo', 'stock_maximo',
        'activo', 'orden',
    ];

    protected $casts = [
        'stock_actual' => 'integer',
        'stock_minimo' => 'integer',
        'stock_maximo' => 'integer',
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function precios()
    {
        return $this->hasMany(PrecioVariante::class);
    }

    /**
     * Precio de la variante en una lista dada.
     * Fallback: precio en la misma lista del producto padre, o precio base.
     */
    public function precioEnLista(?int $listaId): float
    {
        if ($listaId) {
            $pv = $this->precios->firstWhere('lista_precio_id', $listaId);
            if ($pv) {
                return (float) $pv->precio;
            }
        }
        // Fallback al producto padre
        return (float) ($this->producto?->precioEnLista($listaId) ?? 0);
    }

    /** ¿Está en o por debajo del stock mínimo? */
    public function getStockBajoAttribute(): bool
    {
        return $this->stock_minimo > 0 && $this->stock_actual <= $this->stock_minimo;
    }
}
