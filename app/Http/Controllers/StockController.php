<?php

namespace App\Http\Controllers;

use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    /** Listado de inventario con stock y accesos a movimientos. */
    public function index(Request $request)
    {
        $productos = Producto::query()
            ->with('categoria')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where('nombre', 'like', "%{$b}%")->orWhere('referencia', 'like', "%{$b}%");
            })
            ->when($request->estado === 'stock_bajo', fn ($q) => $q->whereColumn('stock_actual', '<=', 'stock_minimo')->where('stock_minimo', '>', 0))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $totalUnidades = Producto::sum('stock_actual');
        $stockBajo = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->where('stock_minimo', '>', 0)->count();

        return view('stock.index', compact('productos', 'totalUnidades', 'stockBajo'));
    }

    /** Kardex completo de un producto. */
    public function kardex(Producto $producto)
    {
        $movimientos = $producto->movimientos()->with('user')->latest()->paginate(20);

        return view('stock.kardex', compact('producto', 'movimientos'));
    }

    /** Registrar un movimiento (entrada / salida / ajuste). */
    public function movimiento(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:entrada,salida,ajuste'],
            'cantidad' => ['required', 'integer', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        // Entrada/salida deben ser al menos 1; el ajuste sí puede fijar stock en 0.
        if ($data['tipo'] !== 'ajuste' && (int) $data['cantidad'] < 1) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser al menos 1 para entradas y salidas.']);
        }

        try {
            $producto->registrarMovimiento($data['tipo'], (int) $data['cantidad'], $data['motivo'] ?? null);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['cantidad' => $e->getMessage()]);
        }

        return back()->with('success', "Movimiento registrado. Stock de «{$producto->nombre}»: {$producto->stock_actual}.");
    }

    /** Bitácora global de movimientos. */
    public function movimientos(Request $request)
    {
        $movimientos = MovimientoStock::query()
            ->with(['producto', 'user'])
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('stock.movimientos', compact('movimientos'));
    }
}
