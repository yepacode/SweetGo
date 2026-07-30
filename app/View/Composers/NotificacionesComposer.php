<?php

namespace App\View\Composers;

use App\Models\Cotizacion;
use App\Models\Garantia;
use App\Models\Notificacion;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificacionesComposer
{
    public function compose(View $view): void
    {
        $u = Auth::user();
        if (! $u) {
            $view->with('notificaciones', ['total' => 0, 'stockBajo' => collect(), 'garantiasAbiertas' => collect(), 'cotizacionesEnviadas' => collect(), 'alertas' => collect()]);
            return;
        }

        $esAdmin = $u->hasRole('admin');

        // Stock bajo (global, siempre): productos con stock <= minimo y minimo > 0.
        $stockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_minimo', '>', 0)
            ->orderBy('stock_actual')
            ->limit(5)
            ->get(['id', 'nombre', 'stock_actual', 'stock_minimo']);

        // Garantías abiertas del usuario (o todas si admin).
        $garantiasAbiertas = Garantia::whereIn('estado', Garantia::ABIERTOS)
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id))
            ->latest()->limit(5)
            ->get(['id', 'numero', 'estado']);

        // Cotizaciones enviadas pendientes de aprobación (del usuario).
        $cotizacionesEnviadas = Cotizacion::where('estado', 'enviada')
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id))
            ->latest()->limit(5)
            ->get(['id', 'numero', 'total']);

        // Alertas dirigidas al usuario (ej. cotización editada por vendedor).
        $alertas = Notificacion::where('para_user_id', $u->id)
            ->noLeidas()
            ->latest()
            ->limit(10)
            ->get(['id', 'tipo', 'titulo', 'mensaje', 'url', 'created_at']);

        $view->with('notificaciones', [
            'total' => $stockBajo->count() + $garantiasAbiertas->count() + $cotizacionesEnviadas->count() + $alertas->count(),
            'stockBajo' => $stockBajo,
            'garantiasAbiertas' => $garantiasAbiertas,
            'cotizacionesEnviadas' => $cotizacionesEnviadas,
            'alertas' => $alertas,
        ]);
    }
}
