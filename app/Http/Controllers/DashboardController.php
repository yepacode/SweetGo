<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Garantia;
use App\Models\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $u = Auth::user();
        $esAdmin = $u->hasRole('admin');
        $inicioMes = Carbon::now()->startOfMonth();

        // Scope base según rol: vendedor solo ve lo suyo.
        $ventasBase = Cotizacion::where('estado', 'aprobada')
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id));
        $enCursoBase = Cotizacion::whereIn('estado', ['borrador', 'enviada'])
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id));
        $clientesBase = Cliente::query()->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id));
        $garantiasBase = Garantia::whereIn('estado', Garantia::ABIERTOS)
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id));

        $ventasMes = (clone $ventasBase)->where('aprobada_at', '>=', $inicioMes)->sum('total');
        $cotizacionesEnCurso = $enCursoBase->count();
        $productosActivos = Producto::where('activo', true)->count();
        $stockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_minimo', '>', 0)->count();

        $clientesNuevos = (clone $clientesBase)->where('created_at', '>=', $inicioMes)->count();
        $garantiasAbiertas = $garantiasBase->count();

        // Productos más vendidos (por cotizaciones aprobadas del scope)
        $masVendidos = CotizacionItem::query()
            ->selectRaw('nombre, SUM(cantidad) as unidades, SUM(subtotal) as total')
            ->whereHas('cotizacion', function ($q) use ($esAdmin, $u) {
                $q->where('estado', 'aprobada')
                    ->when(! $esAdmin, fn ($qq) => $qq->where('user_id', $u->id));
            })
            ->groupBy('nombre')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get();

        // Productos con stock bajo (info global de inventario, siempre visible)
        $productosStockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_minimo', '>', 0)
            ->orderBy('stock_actual')
            ->limit(6)
            ->get(['id', 'nombre', 'stock_actual', 'stock_minimo']);

        // Últimas cotizaciones del scope
        $ultimasCotizaciones = Cotizacion::with('cliente')
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id))
            ->latest()->limit(6)->get();

        // Ventas por día — últimos 30 días
        $desde = Carbon::now()->subDays(29)->startOfDay();
        $ventasPorDia = Cotizacion::selectRaw('DATE(aprobada_at) as fecha, SUM(total) as total_ventas')
            ->where('estado', 'aprobada')
            ->whereNotNull('aprobada_at')
            ->where('aprobada_at', '>=', $desde)
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id))
            ->groupByRaw('DATE(aprobada_at)')
            ->pluck('total_ventas', 'fecha');

        $serie = [];
        for ($i = 0; $i < 30; $i++) {
            $d = $desde->copy()->addDays($i)->toDateString();
            $serie[] = ['fecha' => $d, 'total' => (float) ($ventasPorDia[$d] ?? 0)];
        }

        return view('dashboard', compact(
            'ventasMes', 'cotizacionesEnCurso', 'productosActivos', 'stockBajo',
            'clientesNuevos', 'garantiasAbiertas', 'masVendidos', 'productosStockBajo', 'ultimasCotizaciones',
            'serie'
        ));
    }
}
