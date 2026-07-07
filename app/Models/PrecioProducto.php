<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioProducto extends Model
{
    protected $table = 'precio_productos';

    protected $fillable = ['producto_id', 'lista_precio_id', 'precio'];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function lista()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }
}
