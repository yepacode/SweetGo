<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categoria extends Model
{
    use RegistraBitacora;

    protected $table = 'categorias';

    protected function bitacoraEtiqueta(): string
    {
        return 'Categoría';
    }

    protected $fillable = ['nombre', 'slug', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Categoria $categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nombre);
            }
        });
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
