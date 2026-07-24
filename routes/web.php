<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CotizadorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvioController;
use App\Http\Controllers\GarantiaController;
use App\Http\Controllers\ListaPrecioController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ZonaEnvioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Catálogo PÚBLICO (sin autenticación) — enlace compartible por WhatsApp. Con rate limiting.
Route::get('/c/{token}', [CatalogoController::class, 'publico'])
    ->middleware('throttle:40,1')
    ->name('catalogo.publico');

/*
|--------------------------------------------------------------------------
| Backoffice — requiere sesión y un rol válido (admin o vendedor).
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|vendedor'])->group(function () {

    // Búsqueda global (JSON) — rate limit para evitar abuso
    Route::get('/buscar', BusquedaController::class)
        ->middleware('throttle:60,1')
        ->name('buscar');

    // Perfil (todos)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | SOLO ADMINISTRADOR
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        // Gestión de usuarios (reemplaza el registro público)
        Route::post('usuarios/{usuario}/reset-password', [UsuarioController::class, 'resetPassword'])->name('usuarios.reset-password');
        Route::resource('usuarios', UsuarioController::class)->except(['show']);

        // Listas de precios y precios masivos
        Route::get('listas-precios', [ListaPrecioController::class, 'index'])->name('listas-precios.index');
        Route::post('listas-precios', [ListaPrecioController::class, 'store'])->name('listas-precios.store');
        Route::patch('listas-precios/{lista}', [ListaPrecioController::class, 'update'])->name('listas-precios.update');
        Route::delete('listas-precios/{lista}', [ListaPrecioController::class, 'destroy'])->name('listas-precios.destroy');
        Route::post('listas-precios/guardar', [ListaPrecioController::class, 'guardarPrecios'])->name('listas-precios.guardar');

        // Productos, categorías e inventario — solo administrador
        Route::get('productos/plantilla', [ProductoController::class, 'plantilla'])->name('productos.plantilla');
        Route::post('productos/importar', [ProductoController::class, 'importar'])->name('productos.importar');
        Route::resource('productos', ProductoController::class);
        Route::resource('categorias', CategoriaController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/movimientos', [StockController::class, 'movimientos'])->name('stock.movimientos');
        Route::get('stock/{producto}/kardex', [StockController::class, 'kardex'])->name('stock.kardex');
        Route::post('stock/{producto}/movimiento', [StockController::class, 'movimiento'])->name('stock.movimiento');

        // Zonas de envío (config admin)
        Route::get('zonas-envio', [ZonaEnvioController::class, 'index'])->name('zonas-envio.index');
        Route::post('zonas-envio', [ZonaEnvioController::class, 'store'])->name('zonas-envio.store');
        Route::patch('zonas-envio/{zonaEnvio}', [ZonaEnvioController::class, 'update'])->name('zonas-envio.update');
        Route::patch('zonas-envio/{zonaEnvio}/toggle', [ZonaEnvioController::class, 'toggle'])->name('zonas-envio.toggle');
        Route::delete('zonas-envio/{zonaEnvio}', [ZonaEnvioController::class, 'destroy'])->name('zonas-envio.destroy');

        // Aprobación/rechazo de pagos y cambio de estado de envíos — solo admin
        Route::patch('cotizaciones/{cotizacion}/pagos/{pago}/aprobar', [PagoController::class, 'aprobar'])->name('pagos.aprobar');
        Route::patch('cotizaciones/{cotizacion}/pagos/{pago}/rechazar', [PagoController::class, 'rechazar'])->name('pagos.rechazar');
        Route::patch('cotizaciones/{cotizacion}/envio/{envio}/estado', [EnvioController::class, 'estado'])->name('envio.estado');

        // Garantías — solo administrador (por ahora; guardamos la lógica para un futuro rol)
        Route::patch('garantias/{garantia}/estado', [GarantiaController::class, 'estado'])->name('garantias.estado');
        Route::post('garantias/{garantia}/evidencias', [GarantiaController::class, 'evidencias'])
            ->middleware('throttle:20,1')
            ->name('garantias.evidencias');
        Route::resource('garantias', GarantiaController::class)->except(['edit', 'update']);

        // Links: gestión de enlaces compartibles del catálogo público
        Route::get('links', [CatalogoController::class, 'index'])->name('links.index');
        Route::post('links/enlaces', [CatalogoController::class, 'crearEnlace'])->name('links.enlaces.crear');
        Route::patch('links/enlaces/{enlace}/toggle', [CatalogoController::class, 'toggleEnlace'])->name('links.enlaces.toggle');
        Route::delete('links/enlaces/{enlace}', [CatalogoController::class, 'eliminarEnlace'])->name('links.enlaces.eliminar');

        // Reportes globales
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/inventario/excel', [ReporteController::class, 'inventarioExcel'])->name('reportes.inventario.excel');
        Route::get('reportes/inventario/pdf', [ReporteController::class, 'inventarioPdf'])->name('reportes.inventario.pdf');
        Route::get('reportes/cotizaciones/excel', [ReporteController::class, 'cotizacionesExcel'])->name('reportes.cotizaciones.excel');
        Route::get('reportes/cotizaciones/pdf', [ReporteController::class, 'cotizacionesPdf'])->name('reportes.cotizaciones.pdf');
        Route::get('reportes/clientes/excel', [ReporteController::class, 'clientesExcel'])->name('reportes.clientes.excel');
        Route::get('reportes/clientes/pdf', [ReporteController::class, 'clientesPdf'])->name('reportes.clientes.pdf');

        // Bitácora de auditoría
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');

        // Eliminaciones sensibles
        Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::delete('cotizaciones/{cotizacion}', [CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');
        Route::delete('garantias/{garantia}', [GarantiaController::class, 'destroy'])->name('garantias.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | COMPARTIDO admin + vendedor
    |----------------------------------------------------------------------
    */
    // Clientes (sin eliminar)
    Route::resource('clientes', ClienteController::class)->except(['destroy']);

    // Catálogo interactivo (flujo cliente → productos con precios → carrito → cotización)
    Route::get('catalogo', [CotizadorController::class, 'index'])->name('catalogo.index');
    Route::post('catalogo', [CotizadorController::class, 'store'])->name('catalogo.store');

    // Cotizaciones (sin eliminar)
    Route::get('cotizaciones/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');
    Route::patch('cotizaciones/{cotizacion}/estado', [CotizacionController::class, 'estado'])->name('cotizaciones.estado');
    Route::patch('cotizaciones/{cotizacion}/vendedor', [CotizacionController::class, 'reasignarVendedor'])->name('cotizaciones.vendedor');
    Route::post('cotizaciones/{cotizacion}/duplicar', [CotizacionController::class, 'duplicar'])->name('cotizaciones.duplicar');
    // Pagos (vendedor sube; admin aprueba/rechaza arriba)
    Route::post('cotizaciones/{cotizacion}/pagos', [PagoController::class, 'store'])->name('pagos.store');
    Route::get('cotizaciones/{cotizacion}/pagos/{pago}/comprobante', [PagoController::class, 'comprobante'])->name('pagos.comprobante');
    // Envíos (vendedor y admin pueden configurar; solo admin cambia estado, arriba)
    Route::post('cotizaciones/{cotizacion}/envio', [EnvioController::class, 'store'])->name('envio.store');
    Route::resource('cotizaciones', CotizacionController::class)->except(['destroy'])->parameters(['cotizaciones' => 'cotizacion']);

});

require __DIR__.'/auth.php';
