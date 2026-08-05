<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use Illuminate\Console\Command;

/**
 * Elimina una cotización de forma SEGURA. Si había descontado inventario, primero
 * REPONE el stock (revertirStock) y luego borra la cotización. Los ítems, pagos y
 * envío se eliminan en cascada por FK. Útil para quitar una cotización errónea y que
 * el consecutivo (COT-XXXX) continúe desde ese número.
 *
 * Uso:
 *   php artisan sweetgo:cotizacion-eliminar 12            (dry-run)
 *   php artisan sweetgo:cotizacion-eliminar COT-0012 --force
 */
class CotizacionEliminar extends Command
{
    protected $signature = 'sweetgo:cotizacion-eliminar {numero : Número de cotización (ej: 12 o COT-0012)} {--force : Aplica el borrado (sin este flag es dry-run)}';

    protected $description = 'Elimina una cotización reponiendo su stock si lo había descontado.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->info('Modo: ' . ($force ? 'APLICANDO' : 'DRY-RUN (sin cambios)'));

        $digitos = preg_replace('/\D/', '', $this->argument('numero'));
        $numero = 'COT-' . str_pad($digitos === '' ? '0' : $digitos, 4, '0', STR_PAD_LEFT);

        $cot = Cotizacion::where('numero', $numero)->first();
        if (! $cot) {
            $this->error("{$numero}: no existe.");
            return self::FAILURE;
        }

        $this->newLine();
        $this->line("Cotización: {$numero} · {$cot->cliente?->nombre}");
        $this->line("  estado: {$cot->estado}  | total: \$" . number_format($cot->total) .
            "  | descontó inventario: " . ($cot->stock_descontado ? 'SÍ (se repondrá)' : 'no'));
        $this->line("  ítems: " . $cot->items()->count() . "  | pagos: " . $cot->pagos()->count() .
            " (se borran en cascada)");

        if (! $force) {
            $this->newLine();
            $this->warn('Dry-run: corré con --force para eliminarla.');
            return self::SUCCESS;
        }

        if ($cot->stock_descontado) {
            $cot->revertirStock();   // repone inventario (movimientos de entrada)
            $this->info('  ✔ Stock repuesto.');
        }

        $cot->delete();
        $this->info("✅ {$numero} eliminada. El siguiente consecutivo será {$numero}.");

        return self::SUCCESS;
    }
}
