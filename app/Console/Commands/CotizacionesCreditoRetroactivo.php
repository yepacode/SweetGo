<?php

namespace App\Console\Commands;

use App\Models\Cotizacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Detecta cotizaciones que hoy están marcadas como `pagada` pero cuyos pagos aprobados
 * son 100% de tipo `credito` (o sea, no entró dinero real). Antes del fix del jul-2026
 * el sistema las marcaba pagada al aprobar el crédito; ahora deberían estar en `credito`.
 * Este comando las detecta y las corrige.
 *
 * Uso:
 *   php artisan sweetgo:cotizaciones-credito-retroactivo             (dry-run)
 *   php artisan sweetgo:cotizaciones-credito-retroactivo --force     (aplica)
 */
class CotizacionesCreditoRetroactivo extends Command
{
    protected $signature = 'sweetgo:cotizaciones-credito-retroactivo {--force}';
    protected $description = 'Cambia a estado `credito` las cotizaciones marcadas `pagada` cuyos pagos aprobados son todos crédito.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->info('Modo: ' . ($force ? 'APLICANDO' : 'DRY-RUN'));
        $this->newLine();

        // Cotizaciones pagada que TIENEN pagos aprobados pero NINGUNO es no-crédito.
        $candidatas = Cotizacion::where('estado', 'pagada')
            ->whereHas('pagos', fn ($p) => $p->where('estado', 'aprobado'))
            ->get()
            ->filter(function ($cot) {
                $tieneNoCredito = $cot->pagos()
                    ->where('estado', 'aprobado')
                    ->where('metodo', '!=', 'credito')
                    ->exists();
                return ! $tieneNoCredito;  // solo crédito → mal marcada como pagada
            });

        if ($candidatas->isEmpty()) {
            $this->info('✅ No hay cotizaciones mal marcadas. Nada que hacer.');
            return self::SUCCESS;
        }

        $this->warn("Detectadas {$candidatas->count()} cotizaciones marcadas 'pagada' cuyo único pago aprobado es crédito:");
        foreach ($candidatas as $cot) {
            $this->line("  • {$cot->numero} · {$cot->cliente?->nombre} · total \${$cot->total}");
            if ($force) {
                $cot->update(['estado' => 'credito']);
            }
        }

        $this->newLine();
        if ($force) {
            $this->info("✅ {$candidatas->count()} cotizaciones pasadas a estado `credito`.");
        } else {
            $this->warn('Dry-run: usá --force para aplicar.');
        }
        return self::SUCCESS;
    }
}
