<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    protected $table = 'movimiento_stocks';

    protected $fillable = [
        'producto_id', 'tipo', 'cantidad',
        'stock_anterior', 'stock_nuevo', 'motivo', 'referencia', 'user_id',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
