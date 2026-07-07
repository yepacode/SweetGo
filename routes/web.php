<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GarantiaController;
use App\Http\Controllers\ListaPrecioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UsuarioController;
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

        // Productos: importación y eliminación (antes del resource compartido para evitar colisión con {producto})
        Route::get('productos/plantilla', [ProductoController::class, 'plantilla'])->name('productos.plantilla');
        Route::post('productos/importar', [ProductoController::class, 'importar'])->name('productos.importar');
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');
        Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

        // Catálogo público: gestión de enlaces
        Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
        Route::post('catalogo/enlaces', [CatalogoController::class, 'crearEnlace'])->name('catalogo.enlaces.crear');
        Route::patch('catalogo/enlaces/{enlace}/toggle', [CatalogoController::class, 'toggleEnlace'])->name('catalogo.enlaces.toggle');
        Route::delete('catalogo/enlaces/{enlace}', [CatalogoController::class, 'eliminarEnlace'])->name('catalogo.enlaces.eliminar');

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
    // Productos e inventario (sin eliminar — es solo admin arriba)
    Route::resource('productos', ProductoController::class)->except(['destroy']);
    Route::resource('categorias', CategoriaController::class)->only(['index', 'store', 'update']);

    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('stock/movimientos', [StockController::class, 'movimientos'])->name('stock.movimientos');
    Route::get('stock/{producto}/kardex', [StockController::class, 'kardex'])->name('stock.kardex');
    Route::post('stock/{producto}/movimiento', [StockController::class, 'movimiento'])->name('stock.movimiento');

    // Clientes (sin eliminar)
    Route::resource('clientes', ClienteController::class)->except(['destroy']);

    // Cotizaciones (sin eliminar)
    Route::get('cotizaciones/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');
    Route::patch('cotizaciones/{cotizacion}/estado', [CotizacionController::class, 'estado'])->name('cotizaciones.estado');
    Route::post('cotizaciones/{cotizacion}/duplicar', [CotizacionController::class, 'duplicar'])->name('cotizaciones.duplicar');
    Route::resource('cotizaciones', CotizacionController::class)->except(['destroy'])->parameters(['cotizaciones' => 'cotizacion']);

    // Garantías (sin editar/actualizar/eliminar)
    Route::patch('garantias/{garantia}/estado', [GarantiaController::class, 'estado'])->name('garantias.estado');
    Route::post('garantias/{garantia}/evidencias', [GarantiaController::class, 'evidencias'])
        ->middleware('throttle:20,1') // máx. 20 uploads por minuto por usuario
        ->name('garantias.evidencias');
    Route::resource('garantias', GarantiaController::class)->except(['edit', 'update', 'destroy']);
});

require __DIR__.'/auth.php';
