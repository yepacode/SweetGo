<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Garantia;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusquedaController extends Controller
{
    /** Búsqueda global JSON: productos, clientes, cotizaciones, garantías. */
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $q = mb_substr($q, 0, 60); // tope de longitud: LIKE de queries gigantes es carga innecesaria

        if (mb_strlen($q) < 2) {
            return response()->json(['q' => $q, 'grupos' => []]);
        }

        $u = Auth::user();
        $esAdmin = $u->hasRole('admin');
        $like = "%{$q}%";

        $productos = Producto::where('activo', true)
            ->where(fn ($qq) => $qq->where('nombre', 'like', $like)->orWhere('referencia', 'like', $like))
            ->orderBy('nombre')->limit(5)
            ->get(['id', 'nombre', 'referencia', 'stock_actual'])
            ->map(fn ($p) => [
                'label' => $p->nombre,
                'sub' => $p->referencia ? "Ref: {$p->referencia} · Stock: {$p->stock_actual}" : "Stock: {$p->stock_actual}",
                'url' => route('productos.show', $p),
            ]);

        $clientes = Cliente::query()
            ->when(! $esAdmin, fn ($qq) => $qq->where('user_id', $u->id))
            ->where(fn ($qq) => $qq->where('nombre', 'like', $like)->orWhere('documento', 'like', $like)->orWhere('telefono', 'like', $like))
            ->orderBy('nombre')->limit(5)
            ->get(['id', 'nombre', 'documento', 'ciudad'])
            ->map(fn ($c) => [
                'label' => $c->nombre,
                'sub' => trim(($c->documento ?? '') . ($c->ciudad ? ' · ' . $c->ciudad : ''), ' ·') ?: '—',
                'url' => route('clientes.show', $c),
            ]);

        $cotizaciones = Cotizacion::with('cliente')
            ->when(! $esAdmin, fn ($qq) => $qq->where('user_id', $u->id))
            ->where(fn ($qq) => $qq->where('numero', 'like', $like)->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', $like)))
            ->orderByDesc('numero')->limit(5)
            ->get(['id', 'numero', 'cliente_id', 'estado', 'total'])
            ->map(fn ($cot) => [
                'label' => $cot->numero . ' · ' . ($cot->cliente?->nombre ?? '—'),
                'sub' => ucfirst($cot->estado) . ' · $' . number_format($cot->total, 0, ',', '.'),
                'url' => route('cotizaciones.show', $cot),
            ]);

        $garantias = Garantia::with('cliente')
            ->when(! $esAdmin, fn ($qq) => $qq->where('user_id', $u->id))
            ->where(fn ($qq) => $qq->where('numero', 'like', $like)->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', $like)))
            ->orderByDesc('numero')->limit(5)
            ->get(['id', 'numero', 'cliente_id', 'estado'])
            ->map(fn ($g) => [
                'label' => $g->numero . ' · ' . ($g->cliente?->nombre ?? '—'),
                'sub' => ucfirst(str_replace('_', ' ', $g->estado)),
                'url' => route('garantias.show', $g),
            ]);

        $grupos = array_values(array_filter([
            $productos->isNotEmpty()    ? ['titulo' => 'Productos',    'items' => $productos] : null,
            $clientes->isNotEmpty()     ? ['titulo' => 'Clientes',     'items' => $clientes] : null,
            $cotizaciones->isNotEmpty() ? ['titulo' => 'Cotizaciones', 'items' => $cotizaciones] : null,
            $garantias->isNotEmpty()    ? ['titulo' => 'Garantías',    'items' => $garantias] : null,
        ]));

        return response()->json(['q' => $q, 'grupos' => $grupos]);
    }
}
