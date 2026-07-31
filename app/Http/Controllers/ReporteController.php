<?php

namespace App\Http\Controllers;

use App\Exports\ClientesExport;
use App\Exports\CotizacionesExport;
use App\Exports\CreditosExport;
use App\Exports\InventarioExport;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);

        $ventasQ = Cotizacion::where('estado', 'aprobada')
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));

        $resumen = [
            'productos' => Producto::count(),
            'clientes' => Cliente::count(),
            'cotizaciones' => Cotizacion::count(),
            'unidades_stock' => Producto::sum('stock_actual'),
            'ventas_periodo' => (clone $ventasQ)->sum('total'),
            'cotizaciones_periodo' => (clone $ventasQ)->count(),
        ];

        return view('reportes.index', compact('resumen', 'desde', 'hasta'));
    }

    private function rango(Request $request): array
    {
        return [
            $request->filled('desde') ? $request->desde : null,
            $request->filled('hasta') ? $request->hasta : null,
        ];
    }

    // ---------- Inventario ----------
    public function inventarioExcel()
    {
        return Excel::download(new InventarioExport, 'inventario_sweetgo_'.date('Ymd').'.xlsx');
    }

    public function inventarioPdf()
    {
        $productos = Producto::with('categoria')->orderBy('nombre')->get();
        $totalUnidades = Producto::sum('stock_actual');

        $pdf = Pdf::loadView('reportes.pdf.inventario', compact('productos', 'totalUnidades'))->setPaper('letter');

        return $pdf->stream('inventario_sweetgo_'.date('Ymd').'.pdf');
    }

    // ---------- Cotizaciones ----------
    public function cotizacionesExcel(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);

        return Excel::download(new CotizacionesExport($desde, $hasta), 'cotizaciones_sweetgo_'.date('Ymd').'.xlsx');
    }

    public function cotizacionesPdf(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);

        $q = Cotizacion::with(['cliente', 'vendedor'])
            ->when($desde, fn ($qq) => $qq->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($qq) => $qq->whereDate('fecha', '<=', $hasta));

        $cotizaciones = (clone $q)->latest()->get();
        $total = (clone $q)->where('estado', 'aprobada')->sum('total');

        $pdf = Pdf::loadView('reportes.pdf.cotizaciones', compact('cotizaciones', 'total', 'desde', 'hasta'))->setPaper('letter', 'landscape');

        return $pdf->stream('cotizaciones_sweetgo_'.date('Ymd').'.pdf');
    }

    // ---------- Cuentas por cobrar (créditos) ----------
    public function creditosExcel(Request $request)
    {
        return Excel::download(
            new CreditosExport($request->cliente ?: null, $request->aging ?: null),
            'cuentas_por_cobrar_sweetgo_'.date('Ymd').'.xlsx'
        );
    }

    public function creditosPdf(Request $request)
    {
        $todos = Cotizacion::enCredito()
            ->with(['cliente', 'vendedor', 'pagos'])
            ->when($request->cliente, fn ($q) => $q->where('cliente_id', $request->cliente))
            ->orderByDesc('id')
            ->get();

        if ($request->filled('aging') && in_array($request->aging, ['vencido', 'por_vencer', 'al_dia', 'sin_plazo'], true)) {
            $todos = $todos->filter(fn ($c) => $c->agingCredito() === $request->aging)->values();
        }

        $totalSaldo = (float) $todos->sum(fn ($c) => $c->saldoCredito());
        $totalVencido = (float) $todos->filter(fn ($c) => $c->agingCredito() === 'vencido')->sum(fn ($c) => $c->saldoCredito());

        $pdf = Pdf::loadView('reportes.pdf.creditos', compact('todos', 'totalSaldo', 'totalVencido'))->setPaper('letter', 'landscape');

        return $pdf->stream('cuentas_por_cobrar_sweetgo_'.date('Ymd').'.pdf');
    }

    // ---------- Clientes ----------
    public function clientesExcel()
    {
        return Excel::download(new ClientesExport, 'clientes_sweetgo_'.date('Ymd').'.xlsx');
    }

    public function clientesPdf()
    {
        $clientes = Cliente::with('listaPrecio')->withCount('cotizaciones')->orderBy('nombre')->get();

        $pdf = Pdf::loadView('reportes.pdf.clientes', compact('clientes'))->setPaper('letter');

        return $pdf->stream('clientes_sweetgo_'.date('Ymd').'.pdf');
    }
}
