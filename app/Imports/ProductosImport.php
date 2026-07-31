<?php

namespace App\Imports;

use App\Models\Categoria;
use App\Models\ListaPrecio;
use App\Models\Producto;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\HeadingRowFormatter;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa productos desde Excel/CSV.
 * Columnas fijas: nombre, referencia, categoria, precio, stock, stock_minimo
 * Columnas dinámicas: "precio_<slug_lista>" para cada lista de precios activa
 *   (ej. precio_normal_publico, precio_mayorista, precio_super_mayorista)
 * - Coincidencia por «nombre»: crea o actualiza.
 * - Si viene stock en una fila nueva, registra un movimiento de entrada (stock inicial).
 * - Los precios por lista se guardan en precio_productos vía updateOrCreate.
 */
class ProductosImport implements ToCollection, WithHeadingRow
{
    public int $creados = 0;
    public int $actualizados = 0;
    public int $omitidos = 0;

    /** Mapa slug_lista => lista_id. Se llena al construir para evitar N+1 en el loop. */
    private array $mapaListas = [];

    public function __construct()
    {
        foreach (ListaPrecio::where('activo', true)->get() as $l) {
            $this->mapaListas['precio_' . Str::slug($l->nombre, '_')] = $l->id;
        }
    }

    /**
     * Parsea precios COP: acepta "8500", "8.500", "8500.00", "8.500,00", "$ 8,500.00", etc.
     * Regla: si contiene coma y punto, el ÚLTIMO separador es decimal; si solo hay uno, se
     * asume separador de miles cuando va seguido de 3 dígitos, si no, decimal.
     */
    private function parsearPrecio($valor): float
    {
        // Números "de verdad" (no strings ambiguas como "8.500") pasan directo.
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }
        $s = preg_replace('/[^0-9.,]/', '', (string) $valor);
        if ($s === '' || $s === null) {
            return 0.0;
        }

        $tienePunto = str_contains($s, '.');
        $tieneComa  = str_contains($s, ',');

        if ($tienePunto && $tieneComa) {
            // El separador decimal es el que aparece más tarde.
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);   // punto = miles
                $s = str_replace(',', '.', $s);  // coma = decimal
            } else {
                $s = str_replace(',', '', $s);   // coma = miles
            }
        } elseif ($tieneComa) {
            // ',' seguido de exactamente 3 dígitos y sin más → miles ("8,500"); si no, decimal.
            $s = preg_match('/,\d{3}$/', $s) && preg_match('/^\d{1,3},\d{3}$/', $s)
                ? str_replace(',', '', $s)
                : str_replace(',', '.', $s);
        } elseif ($tienePunto) {
            // "8.500" con 3 dígitos → miles (típico COP); "8500.00" → decimal.
            $partes = explode('.', $s);
            if (count($partes) > 2 || (count($partes) === 2 && strlen($partes[1]) === 3)) {
                $s = str_replace('.', '', $s);
            }
        }

        return (float) $s;
    }

    public function collection($rows): void
    {
        foreach ($rows as $row) {
            $nombre = trim((string) ($row['nombre'] ?? ''));

            if ($nombre === '') {
                $this->omitidos++;
                continue;
            }

            $categoriaId = null;
            $categoriaNombre = trim((string) ($row['categoria'] ?? ''));
            if ($categoriaNombre !== '') {
                $categoria = Categoria::firstOrCreate(
                    ['slug' => Str::slug($categoriaNombre)],
                    ['nombre' => $categoriaNombre, 'activo' => true]
                );
                $categoriaId = $categoria->id;
            }

            $precio = $this->parsearPrecio($row['precio'] ?? 0);
            $stockMinimo = (int) ($row['stock_minimo'] ?? 0); // 0 como en el form
            $stockMaximo = isset($row['stock_maximo']) && $row['stock_maximo'] !== '' && $row['stock_maximo'] !== null
                ? (int) $row['stock_maximo'] : null;
            $stock = (int) ($row['stock'] ?? 0);

            // Match: primero por referencia (más estable si el cliente renombra); si no, por nombre.
            // Evita crear duplicados cuando se re-corre el import con el mismo Excel.
            $referencia = trim((string) ($row['referencia'] ?? '')) ?: null;
            $existente = null;
            if ($referencia !== null) {
                $existente = Producto::where('referencia', $referencia)->first();
            }
            if (! $existente) {
                $existente = Producto::where('nombre', $nombre)->first();
            }

            if ($existente) {
                $existente->update([
                    'referencia' => $row['referencia'] ?? $existente->referencia,
                    'categoria_id' => $categoriaId ?? $existente->categoria_id,
                    'precio' => $precio ?: $existente->precio,
                    'stock_minimo' => $stockMinimo,
                    'stock_maximo' => $stockMaximo ?? $existente->stock_maximo,
                ]);
                $producto = $existente;
                $this->actualizados++;
            } else {
                $producto = Producto::create([
                    'nombre' => $nombre,
                    'referencia' => $row['referencia'] ?? null,
                    'categoria_id' => $categoriaId,
                    'precio' => $precio,
                    'stock_actual' => 0,
                    'stock_minimo' => $stockMinimo,
                    'stock_maximo' => $stockMaximo,
                    'activo' => true,
                ]);

                if ($stock > 0) {
                    $producto->registrarMovimiento('entrada', $stock, 'Importación masiva');
                }
                $this->creados++;
            }

            // Precios por lista (columnas dinámicas "precio_<slug>").
            foreach ($this->mapaListas as $col => $listaId) {
                if (! isset($row[$col]) || $row[$col] === '' || $row[$col] === null) {
                    continue;
                }
                $precioLista = $this->parsearPrecio($row[$col]);
                if ($precioLista > 0) {
                    $producto->preciosProducto()->updateOrCreate(
                        ['lista_precio_id' => $listaId],
                        ['precio' => $precioLista]
                    );
                }
            }
        }
    }
}
