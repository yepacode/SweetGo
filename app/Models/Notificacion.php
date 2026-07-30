<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = ['para_user_id', 'tipo', 'titulo', 'mensaje', 'url', 'leida_at'];

    protected $casts = [
        'leida_at' => 'datetime',
    ];

    public function paraUser()
    {
        return $this->belongsTo(User::class, 'para_user_id');
    }

    public function scopeNoLeidas($q)
    {
        return $q->whereNull('leida_at');
    }

    /** Crea una notificación por cada admin activo. Útil para eventos que involucran a todo el equipo. */
    public static function alertarAdmins(string $tipo, string $titulo, string $mensaje, ?string $url = null): int
    {
        $admins = User::role('admin')->pluck('id');
        foreach ($admins as $uid) {
            self::create([
                'para_user_id' => $uid,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'url' => $url,
            ]);
        }
        return $admins->count();
    }
}
