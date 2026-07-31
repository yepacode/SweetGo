<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Módulo "Cuentas por cobrar" — muestra todas las cotizaciones en estado `credito`
 * con su aging (vencido / por vencer / al día / sin plazo), abonos aplicados y
 * saldo pendiente. Admin ve todo; vendedor solo lo suyo.
 */
class CreditoController extends Controller
{
    public function index(Request $request)
    {
        $u = Auth::user();
        $esAdmin = $u->hasRole('admin');

        $query = Cotizacion::enCredito()
            ->with(['cliente', 'vendedor', 'pagos'])
            ->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id))
            ->when($request->filled('cliente'), fn ($q) => $q->where('cliente_id', $request->cliente))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(function ($sub) use ($b) {
                    $sub->where('numero', 'like', "%{$b}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$b}%"));
                });
            })
            ->orderByDesc('id');

        // Traemos todo (créditos suelen ser un set acotado); aging se resuelve en PHP.
        $todos = $query->get();

        if ($request->filled('aging') && in_array($request->aging, ['vencido', 'por_vencer', 'al_dia', 'sin_plazo'], true)) {
            $todos = $todos->filter(fn ($c) => $c->agingCredito() === $request->aging)->values();
        }

        $stats = [
            'cuantas' => $todos->count(),
            'total_saldo' => (float) $todos->sum(fn ($c) => $c->saldoCredito()),
            'total_vencido' => (float) $todos->filter(fn ($c) => $c->agingCredito() === 'vencido')->sum(fn ($c) => $c->saldoCredito()),
            'total_por_vencer' => (float) $todos->filter(fn ($c) => $c->agingCredito() === 'por_vencer')->sum(fn ($c) => $c->saldoCredito()),
            'cuentas_vencidas' => $todos->filter(fn ($c) => $c->agingCredito() === 'vencido')->count(),
        ];

        // Clientes con crédito activo, para el filtro (respeta scope del usuario)
        $clientesConCredito = Cliente::whereIn('id',
            Cotizacion::enCredito()->when(! $esAdmin, fn ($q) => $q->where('user_id', $u->id))->pluck('cliente_id')->unique()
        )->orderBy('nombre')->get(['id', 'nombre']);

        return view('creditos.index', compact('todos', 'stats', 'clientesConCredito', 'esAdmin'));
    }
}
