<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioVariante extends Model
{
    protected $table = 'precio_variantes';

    protected $fillable = ['variante_producto_id', 'lista_precio_id', 'precio'];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_producto_id');
    }

    public function lista()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }
}
