<?php

namespace Database\Seeders;

use App\Models\ListaPrecio;
use App\Models\PrecioProducto;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ListasPreciosSeeder extends Seeder
{
    public function run(): void
    {
        $listas = [
            ['nombre' => 'Normal / Público', 'es_publica' => true, 'es_predeterminada' => true],
            ['nombre' => 'Mayorista', 'es_publica' => false, 'es_predeterminada' => false],
            ['nombre' => 'Super Mayorista', 'es_publica' => false, 'es_predeterminada' => false],
        ];

        $creadas = [];
        foreach ($listas as $l) {
            $creadas[] = ListaPrecio::firstOrCreate(
                ['slug' => Str::slug($l['nombre'])],
                $l + ['activo' => true]
            );
        }

        // Sembrar el precio base en cada lista para cada producto (si no existe).
        foreach (Producto::all() as $producto) {
            foreach ($creadas as $lista) {
                PrecioProducto::firstOrCreate(
                    ['producto_id' => $producto->id, 'lista_precio_id' => $lista->id],
                    ['precio' => $producto->precio]
                );
            }
        }
    }
}
