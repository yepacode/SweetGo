<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListaPrecio extends Model
{
    use RegistraBitacora;

    protected $table = 'lista_precios';

    protected function bitacoraEtiqueta(): string
    {
        return 'Lista de precios';
    }

    protected $fillable = ['nombre', 'slug', 'es_publica', 'es_predeterminada', 'activo'];

    protected $casts = [
        'es_publica' => 'boolean',
        'es_predeterminada' => 'boolean',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ListaPrecio $lista) {
            if (empty($lista->slug)) {
                $lista->slug = Str::slug($lista->nombre);
            }
        });
    }

    public function precios()
    {
        return $this->hasMany(PrecioProducto::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    /** La lista que ve el catálogo público (o la predeterminada como respaldo). */
    public static function publica(): ?self
    {
        return static::where('es_publica', true)->first()
            ?? static::where('es_predeterminada', true)->first();
    }

    public static function predeterminada(): ?self
    {
        return static::where('es_predeterminada', true)->first();
    }
}
