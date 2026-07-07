<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EnlaceCatalogo extends Model
{
    use RegistraBitacora;

    protected array $bitacoraIgnorar = ['visitas', 'ultima_visita']; // las visitas del público no son acciones de usuario

    protected $table = 'enlace_catalogos';

    protected function bitacoraEtiqueta(): string
    {
        return 'Enlace de catálogo';
    }

    protected $fillable = ['token', 'titulo', 'activo', 'visitas', 'ultima_visita'];

    protected $casts = [
        'activo' => 'boolean',
        'ultima_visita' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (EnlaceCatalogo $enlace) {
            if (empty($enlace->token)) {
                $enlace->token = Str::lower(Str::random(10));
            }
        });
    }

    public function getUrlAttribute(): string
    {
        return route('catalogo.publico', $this->token);
    }
}
