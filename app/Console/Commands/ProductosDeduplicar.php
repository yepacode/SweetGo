<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detecta productos duplicados (mismo nombre + misma referencia) y los consolida
 * en un único registro canónico (el ID más bajo). Migra al canónico:
 *  - movimientos de stock
 *  - items de cotización
 *  - garantías
 *  - precios por lista (dedup por lista, se queda con el del canónico si ya existe)
 *  - variantes
 * Y suma los stocks de los duplicados al canónico antes de borrarlos.
 *
 * Uso:
 *   php artisan sweetgo:productos-deduplicar             (dry-run: solo reporta)
 *   php artisan sweetgo:productos-deduplicar --force     (aplica los cambios)
 */
class ProductosDeduplicar extends Command
{
    protected $signature = 'sweetgo:productos-deduplicar
        {--force : Aplica los cambios (sin este flag es dry-run)}
        {--suffix-refs : Ademas, a las referencias repetidas entre productos de DISTINTO nombre les agrega sufijo -2, -3... para que queden unicas (requiere --force)}';

    protected $description = 'Detecta y consolida productos duplicados (mismo nombre + referencia). Dry-run por defecto.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $modo = $force ? 'APLICANDO CAMBIOS' : 'DRY-RUN (sin cambios)';
        $this->info("Modo: {$modo}");
        $this->newLine();

        // Agrupa por (nombre + referencia). Si dos filas tienen mismo nombre pero distinta
        // referencia NO se consolidan (podría ser un producto diferente por más que se llame igual).
        $grupos = Producto::selectRaw('nombre, referencia, COUNT(*) AS cuantos, GROUP_CONCAT(id ORDER BY id) AS ids, SUM(stock_actual) AS stock_total')
            ->groupBy('nombre', 'referencia')
            ->having('cuantos', '>', 1)
            ->get();

        if ($grupos->isEmpty()) {
            $this->info('✅ No hay productos duplicados. Nada que hacer.');
            return self::SUCCESS;
        }

        $this->warn("Detectados {$grupos->count()} grupos duplicados:");
        $totalFundidos = 0;

        foreach ($grupos as $g) {
            $ids = array_map('intval', explode(',', $g->ids));
            $canonId = $ids[0];
            $dupIds = array_slice($ids, 1);

            $ref = $g->referencia ?: '(sin ref)';
            $this->line("  • «{$g->nombre}» [{$ref}]  x{$g->cuantos}  ids={$g->ids}  stock_total={$g->stock_total}");
            $this->line("      → canónico id={$canonId}, fundir " . implode(',', $dupIds));

            if (! $force) {
                $totalFundidos += count($dupIds);
                continue;
            }

            DB::transaction(function () use ($canonId, $dupIds) {
                // Migrar dependencias
                foreach ($dupIds as $dupId) {
                    DB::table('movimiento_stocks')->where('producto_id', $dupId)->update(['producto_id' => $canonId]);
                    DB::table('cotizacion_items')->where('producto_id', $dupId)->update(['producto_id' => $canonId]);
                    DB::table('garantias')->where('producto_id', $dupId)->update(['producto_id' => $canonId]);
                    DB::table('variantes_producto')->where('producto_id', $dupId)->update(['producto_id' => $canonId]);

                    // precio_productos: UNIQUE(producto_id, lista_precio_id) — evitar dup fila
                    $preciosDup = DB::table('precio_productos')->where('producto_id', $dupId)->get();
                    foreach ($preciosDup as $pp) {
                        $existeEnCanon = DB::table('precio_productos')
                            ->where('producto_id', $canonId)
                            ->where('lista_precio_id', $pp->lista_precio_id)
                            ->exists();
                        if ($existeEnCanon) {
                            DB::table('precio_productos')->where('id', $pp->id)->delete();
                        } else {
                            DB::table('precio_productos')->where('id', $pp->id)->update(['producto_id' => $canonId]);
                        }
                    }
                }

                // Sumar stocks al canónico y luego borrar duplicados
                $sumaStock = Producto::whereIn('id', $dupIds)->sum('stock_actual');
                if ($sumaStock > 0) {
                    Producto::whereKey($canonId)->increment('stock_actual', $sumaStock);
                }
                Producto::whereIn('id', $dupIds)->delete();
            });

            $totalFundidos += count($dupIds);
        }

        $this->newLine();
        if ($force) {
            $this->info("✅ Consolidados: {$totalFundidos} duplicados fusionados en sus respectivos canónicos.");
        } else {
            $this->warn("Dry-run: se fusionarían {$totalFundidos} duplicados. Corré con --force para aplicar.");
        }

        // --- Referencias repetidas entre productos de DISTINTO nombre ---
        // Estas NO se fusionan (son productos distintos), pero rompen el índice UNIQUE.
        $this->newLine();
        $this->reportarReferenciasDuplicadas($force);

        return self::SUCCESS;
    }

    /**
     * Detecta referencias compartidas por productos distintos (que quedan tras la fusión).
     * Con --suffix-refs + --force les agrega -2, -3... para que el índice UNIQUE pueda crearse.
     */
    private function reportarReferenciasDuplicadas(bool $force): void
    {
        $suffix = (bool) $this->option('suffix-refs');

        $refs = Producto::selectRaw('referencia, COUNT(*) AS cuantos, GROUP_CONCAT(id ORDER BY id) AS ids, GROUP_CONCAT(nombre ORDER BY id SEPARATOR " || ") AS nombres')
            ->whereNotNull('referencia')
            ->where('referencia', '!=', '')
            ->groupBy('referencia')
            ->having('cuantos', '>', 1)
            ->get();

        if ($refs->isEmpty()) {
            $this->info('✅ No quedan referencias duplicadas. El índice UNIQUE se puede crear (php artisan migrate).');
            return;
        }

        $this->warn("⚠ Hay {$refs->count()} REFERENCIAS repetidas entre productos DISTINTOS (bloquean el UNIQUE):");
        foreach ($refs as $r) {
            $this->line("  • ref «{$r->referencia}»  x{$r->cuantos}  ids={$r->ids}");
            $this->line("      nombres: {$r->nombres}");
        }

        if (! $suffix) {
            $this->newLine();
            $this->warn('Estas son productos DIFERENTES con la misma referencia. Opciones:');
            $this->line('  a) Corregí la referencia correcta de cada uno desde la web (Productos → Editar).');
            $this->line('  b) O corré:  php artisan sweetgo:productos-deduplicar --force --suffix-refs');
            $this->line('     (deja la 1a igual y a las demás les pone -2, -3... para que sean unicas; luego las editás)');
            return;
        }

        if (! $force) {
            $this->error('--suffix-refs requiere --force para aplicar cambios.');
            return;
        }

        $renombrados = 0;
        foreach ($refs as $r) {
            $ids = array_map('intval', explode(',', $r->ids));
            array_shift($ids); // la primera conserva la referencia original
            $n = 2;
            foreach ($ids as $id) {
                Producto::whereKey($id)->update(['referencia' => $r->referencia . '-' . $n]);
                $n++;
                $renombrados++;
            }
        }
        $this->info("✅ {$renombrados} referencias renombradas con sufijo. Ahora podés correr: php artisan migrate");
    }
}
