<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use Illuminate\Console\Command;

/**
 * Marca como `credito` (venta a plazo / cuenta por cobrar) una lista de cotizaciones
 * indicadas por número. Es para corregir manualmente ventas que quedaron en otro estado
 * pero en realidad son a crédito (el cliente aún debe la plata).
 *
 * NO toca el stock: solo cambia el estado. Si una cotización aún no había descontado
 * inventario (no era una venta confirmada) lo avisa para revisarla a mano.
 *
 * Uso:
 *   php artisan sweetgo:cotizaciones-a-credito 1 3 5 6 7 8 9 11            (dry-run)
 *   php artisan sweetgo:cotizaciones-a-credito COT-0001 COT-0003 --force   (aplica)
 *   (acepta "1", "0001" o "COT-0001", da igual)
 */
class CotizacionesACredito extends Command
{
    protected $signature = 'sweetgo:cotizaciones-a-credito {numeros* : Números de cotización (ej: 1 3 5 o COT-0001)} {--force : Aplica los cambios (sin este flag es dry-run)}';

    protected $description = 'Marca una lista de cotizaciones como `credito` (cuenta por cobrar).';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->info('Modo: ' . ($force ? 'APLICANDO' : 'DRY-RUN (sin cambios)'));
        $this->newLine();

        $cambiadas = 0;

        foreach ($this->argument('numeros') as $entrada) {
            $numero = $this->normalizar($entrada);
            $cot = Cotizacion::where('numero', $numero)->first();

            if (! $cot) {
                $this->error("  ✗ {$numero}: no existe. Saltada.");
                continue;
            }

            $abonosReales = (float) $cot->totalAbonado();   // efectivo/transferencia/tarjeta aprobados
            $saldo = (float) $cot->saldoCredito();           // total - abonos reales

            $this->line("  • {$numero} · {$cot->cliente?->nombre}");
            $this->line("      estado actual: {$cot->estado}  →  credito");
            $this->line("      total: \$" . number_format($cot->total) .
                "  | abonos reales: \$" . number_format($abonosReales) .
                "  | saldo por cobrar: \$" . number_format($saldo));

            if ($cot->estado === 'credito') {
                $this->line('      (ya estaba en credito, se deja igual)');
                continue;
            }
            if (! $cot->stock_descontado) {
                $this->warn('      ⚠ Esta cotización NO ha descontado inventario (no es venta confirmada). Revisar si corresponde.');
            }
            if ($abonosReales > 0) {
                $this->warn('      ⚠ Ya tiene abonos reales; el saldo por cobrar quedará en $' . number_format($saldo) . '.');
            }

            if ($force) {
                $cot->update(['estado' => 'credito']);
            }
            $cambiadas++;
        }

        $this->newLine();
        if ($force) {
            $this->info("✅ {$cambiadas} cotizaciones marcadas como credito.");
        } else {
            $this->warn("Dry-run: se cambiarían {$cambiadas} cotizaciones. Corré con --force para aplicar.");
        }

        return self::SUCCESS;
    }

    /** Acepta "1", "0001", "COT-0001", "cot 1"… y devuelve "COT-0001". */
    private function normalizar(string $entrada): string
    {
        $digitos = preg_replace('/\D/', '', $entrada);
        return 'COT-' . str_pad($digitos === '' ? '0' : $digitos, 4, '0', STR_PAD_LEFT);
    }
}
