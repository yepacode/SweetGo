<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Bitacora extends Model
{
    protected $table = 'bitacoras';

    protected $fillable = ['user_id', 'accion', 'modelo', 'modelo_id', 'descripcion', 'cambios', 'ip'];

    protected $casts = [
        'cambios' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Registra una entrada de bitácora. */
    public static function registrar(string $accion, string $descripcion, ?string $modelo = null, $modeloId = null, ?array $cambios = null): void
    {
        static::create([
            'user_id' => Auth::id(),
            'accion' => $accion,
            'modelo' => $modelo,
            'modelo_id' => $modeloId,
            'descripcion' => $descripcion,
            'cambios' => $cambios,
            'ip' => request()->ip(),
        ]);
    }

    public function accionBadge(): string
    {
        return match ($this->accion) {
            'creó'          => 'bg-green-100 text-green-700',
            'actualizó'     => 'bg-sweetgo-turquoise-light text-teal-700',
            'eliminó'       => 'bg-red-100 text-red-600',
            'inició sesión' => 'bg-sweetgo-pink-light text-sweetgo-pink',
            'cerró sesión'  => 'bg-gray-100 text-gray-600',
            'movimiento'    => 'bg-amber-100 text-amber-700',
            'reversión'     => 'bg-orange-100 text-orange-700',
            default         => 'bg-gray-100 text-gray-600',
        };
    }
}
