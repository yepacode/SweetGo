<?php

namespace App\Console\Commands;

use App\Models\Producto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrige el inventario inflado por la deduplicación de productos (31-jul-2026).
 *
 * Contexto: la carga inicial de inventario (Excel 22-jul) se importó DOS veces, creando
 * un producto duplicado por cada uno. Al deduplicar, se SUMARON los stocks de ambas copias,
 * dejando el inventario al DOBLE. Cada producto afectado tiene en su kardex 2 "Carga inicial"
 * idénticas de valor V, y su stock_actual = 2V - ventas. El stock correcto = V - ventas.
 *
 * Este comando registra un ajuste al valor correcto (stock_actual - V) SOLO en los productos
 * con 2 cargas iniciales iguales, dejando rastro en el kardex. Es idempotente: si ya se corrigió
 * un producto (existe el movimiento de corrección), lo salta.
 *
 * Uso:
 *   php artisan sweetgo:corregir-stock-duplicado           (dry-run: solo reporta)
 *   php artisan sweetgo:corregir-stock-duplicado --force   (aplica los ajustes)
 */
class CorregirStockDuplicado extends Command
{
    protected $signature = 'sweetgo:corregir-stock-duplicado {--force : Aplica los ajustes (sin este flag es dry-run)}';

    protected $description = 'Corrige el inventario inflado al doble por la deduplicación (resta la carga inicial duplicada).';

    private const MOTIVO = 'Corrección: carga inicial duplicada por deduplicación (31-jul-2026)';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->info('Modo: ' . ($force ? 'APLICANDO AJUSTES' : 'DRY-RUN (sin cambios)'));
        $this->newLine();

        $corregidos = 0;
        $saltados = 0;
        $sumaQuitar = 0;

        $this->line(str_pad('PRODUCTO', 40) . str_pad('AHORA', 9) . str_pad('CargaIni', 10) . 'CORRECTO');
        $this->line(str_repeat('-', 68));

        foreach (Producto::orderBy('id')->get() as $p) {
            $cargas = DB::table('movimiento_stocks')
                ->where('producto_id', $p->id)
                ->where('motivo', 'like', 'Carga inicial%')
                ->pluck('cantidad')
                ->all();

            // Solo productos con exactamente 2 cargas iniciales IGUALES (los que se doblaron).
            if (count($cargas) !== 2 || $cargas[0] !== $cargas[1]) {
                continue;
            }

            // Idempotencia: si ya tiene el movimiento de corrección, saltar.
            $yaCorregido = DB::table('movimiento_stocks')
                ->where('producto_id', $p->id)
                ->where('motivo', self::MOTIVO)
                ->exists();
            if ($yaCorregido) {
                $saltados++;
                continue;
            }

            $V = (int) $cargas[0];
            $correcto = (int) $p->stock_actual - $V;

            if ($correcto < 0) {
                $this->error("  ⚠ {$p->nombre}: correcto quedaría negativo ({$correcto}). SALTADO, revisar a mano.");
                $saltados++;
                continue;
            }

            $this->line(
                str_pad(substr($p->nombre, 0, 38), 40) .
                str_pad((string) $p->stock_actual, 9) .
                str_pad('-' . $V, 10) .
                $correcto
            );

            $sumaQuitar += ($p->stock_actual - $correcto);

            if ($force) {
                $p->registrarMovimiento('ajuste', $correcto, self::MOTIVO);
            }

            $corregidos++;
        }

        $this->newLine();
        if ($force) {
            $this->info("✅ Corregidos: {$corregidos} productos. Unidades fantasma removidas: " . number_format($sumaQuitar));
        } else {
            $this->warn("Dry-run: se corregirían {$corregidos} productos ({$saltados} saltados). " .
                "Unidades a remover: " . number_format($sumaQuitar) . ". Corré con --force para aplicar.");
        }

        return self::SUCCESS;
    }
}
