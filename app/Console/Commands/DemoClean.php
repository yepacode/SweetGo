<?php

namespace App\Console\Commands;

use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EnlaceCatalogo;
use App\Models\Garantia;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class DemoClean extends Command
{
    protected $signature = 'sweetgo:demo-clean
                            {--keep-products : Conserva el catálogo de productos y categorías (por defecto sí se conservan)}
                            {--reset-admin-password= : Nueva contraseña para el usuario admin}
                            {--force : No pide confirmación}';

    protected $description = 'Limpia datos operativos (clientes, cotizaciones, garantías, movimientos, bitácora, enlaces) para dejar el sistema listo para producción. Conserva productos y categorías por defecto.';

    public function handle()
    {
        $this->warn('Sweet Go · Limpieza de datos demo/prueba');
        $this->line('Esta acción borrará todos los CLIENTES, COTIZACIONES, GARANTÍAS, MOVIMIENTOS, ENLACES DE CATÁLOGO y BITÁCORA.');
        $this->line('Se conservan usuarios, productos, categorías, listas de precios y el precio base.');

        if (! $this->option('force') && ! $this->confirm('¿Continuar?')) {
            $this->line('Cancelado.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Orden importa por FK RESTRICT en clientes:
            Cotizacion::query()->delete();
            Garantia::query()->delete();
            Cliente::query()->delete();
            MovimientoStock::query()->delete();
            EnlaceCatalogo::query()->delete();
            Bitacora::query()->delete();
            Producto::query()->update(['stock_actual' => 0]);
        });

        // Higiene de disco: archivos físicos de evidencias que quedaron huérfanos.
        Storage::disk('public')->deleteDirectory('garantias');

        $this->info('✓ Datos operativos limpiados. Productos con stock = 0. Archivos de evidencias borrados.');

        // Usuarios de prueba: dejar solo al admin, con contraseña cambiada.
        $eliminados = User::whereNotIn('email', ['admin@sweetgo.com'])->delete();
        if ($eliminados) {
            $this->info("✓ Eliminados $eliminados usuario(s) de prueba (solo se conserva admin@sweetgo.com).");
        }

        if ($nueva = $this->option('reset-admin-password')) {
            $admin = User::where('email', 'admin@sweetgo.com')->first();
            if ($admin) {
                $admin->update(['password' => Hash::make($nueva)]);
                $this->info('✓ Contraseña de admin actualizada.');
            }
        } else {
            $this->comment('⚠ No olvides cambiar la contraseña del admin (`admin@sweetgo.com` sigue con "password") antes de subir a producción.');
        }

        $this->info('Listo. Sistema preparado para producción. Puedes crear los usuarios reales desde /usuarios.');

        return self::SUCCESS;
    }
}
