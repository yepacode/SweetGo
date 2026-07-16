<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\ListaPrecio;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    /** Admin ve todo; vendedor solo lo suyo. Huérfanas (user_id null) NO son accesibles por vendedores. */
    private function autorizarAcceso(Cotizacion $cotizacion): void
    {
        $u = Auth::user();
        if (! $u->hasRole('admin') && $cotizacion->user_id !== $u->id) {
            abort(403, 'No tienes acceso a esta cotización.');
        }
    }

    /** Datos compartidos por create/edit: clientes, productos con precios por lista, etc. */
    private function datosFormulario(): array
    {
        $clientes = Cliente::where('activo', true)
            ->visiblesPara(Auth::user())
            ->orderBy('nombre')->get();

        $productos = Producto::where('activo', true)
            ->with('preciosProducto')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'referencia' => $p->referencia,
                'precio' => (float) $p->precio,
                'stock_actual' => $p->stock_actual,
                'precios' => $p->preciosProducto->mapWithKeys(fn ($pp) => [$pp->lista_precio_id => (float) $pp->precio]),
            ]);

        $clientes->load('listaPrecio');

        return [
            'clientes' => $clientes,
            'productos' => $productos,
            'clientesLista' => $clientes->mapWithKeys(fn ($c) => [$c->id => $c->lista_precio_id])->toArray(),
            'listasNombres' => ListaPrecio::pluck('nombre', 'id')->toArray(),
            'predeterminadaId' => ListaPrecio::predeterminada()?->id,
        ];
    }

    public function index(Request $request)
    {
        $u = Auth::user();

        $cotizaciones = Cotizacion::query()
            ->with(['cliente', 'vendedor'])
            ->when(! $u->hasRole('admin'), fn ($q) => $q->where('user_id', $u->id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(function ($sub) use ($b) {
                    $sub->where('numero', 'like', "%{$b}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$b}%"));
                });
            })
            ->orderByDesc('numero') // COT-0009 arriba
            ->paginate(15)
            ->withQueryString();

        return view('cotizaciones.index', compact('cotizaciones'));
    }

    public function create(Request $request)
    {
        // El único punto de creación de cotizaciones ahora es el catálogo interactivo (tarjetas,
        // modal de detalle, carrito lateral). Si venía un cliente preseleccionado, lo llevamos.
        return redirect()->route('catalogo.index', array_filter([
            'cliente' => $request->query('cliente'),
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Un vendedor no puede cotizar para un cliente que no sea suyo.
        abort_unless(
            Cliente::visiblesPara(Auth::user())->whereKey($data['cliente_id'])->exists(),
            403,
            'No puedes crear una cotización para un cliente ajeno.'
        );

        $cotizacion = DB::transaction(function () use ($data) {
            $cot = Cotizacion::crearConNumero([
                'cliente_id' => $data['cliente_id'],
                'user_id' => Auth::id(),
                'estado' => 'borrador',
                'fecha' => $data['fecha'],
                'validez' => $data['validez'] ?? null,
                'descuento' => $data['descuento'] ?? 0,
                'notas' => $data['notas'] ?? null,
            ]);

            $this->guardarItems($cot, $data['items']);
            $cot->recalcularTotales();

            return $cot;
        });

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', "Cotización {$cotizacion->numero} creada.");
    }

    public function show(Cotizacion $cotizacion)
    {
        $this->autorizarAcceso($cotizacion);

        $cotizacion->load(['cliente', 'vendedor', 'items']);

        return view('cotizaciones.show', compact('cotizacion'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        $this->autorizarAcceso($cotizacion);

        if (! $cotizacion->esEditable()) {
            return redirect()->route('cotizaciones.show', $cotizacion)
                ->with('error', 'Esta cotización ya no se puede editar (tiene pagos registrados o cambió de estado).');
        }

        $cotizacion->load('items');
        $itemsIniciales = old('items') ?: $cotizacion->items->map(fn ($i) => [
            'producto_id' => $i->producto_id,
            'cantidad' => $i->cantidad,
            'precio_unitario' => (float) $i->precio_unitario,
        ])->values()->all();

        return view('cotizaciones.edit', $this->datosFormulario() + compact('cotizacion', 'itemsIniciales'));
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        $this->autorizarAcceso($cotizacion);

        if (! $cotizacion->esEditable()) {
            return redirect()->route('cotizaciones.show', $cotizacion)
                ->with('error', 'Esta cotización ya no se puede editar (tiene pagos registrados o cambió de estado).');
        }

        $data = $this->validated($request);

        // También al actualizar: el cliente debe ser suyo.
        abort_unless(
            Cliente::visiblesPara(Auth::user())->whereKey($data['cliente_id'])->exists(),
            403,
            'No puedes asignar esta cotización a un cliente ajeno.'
        );

        DB::transaction(function () use ($cotizacion, $data) {
            $cotizacion->update([
                'cliente_id' => $data['cliente_id'],
                'fecha' => $data['fecha'],
                'validez' => $data['validez'] ?? null,
                'descuento' => $data['descuento'] ?? 0,
                'notas' => $data['notas'] ?? null,
            ]);

            $cotizacion->items()->delete();
            $this->guardarItems($cotizacion, $data['items']);
            $cotizacion->recalcularTotales();
        });

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', "Cotización {$cotizacion->numero} actualizada.");
    }

    public function destroy(Cotizacion $cotizacion)
    {
        $numero = $cotizacion->numero;
        $cotizacion->delete();

        return redirect()->route('cotizaciones.index')
            ->with('success', "Cotización {$numero} eliminada.");
    }

    /** Cambia el estado: enviada | aprobada | rechazada. */
    public function estado(Request $request, Cotizacion $cotizacion)
    {
        $this->autorizarAcceso($cotizacion);

        $request->validate(['estado' => ['required', 'in:enviada,aprobada,rechazada']]);
        $nuevo = $request->estado;

        // "aprobada" y "rechazada" (que puede revertir stock) son solo admin.
        // El vendedor solo puede marcar "enviada" (para pedir revisión).
        if (in_array($nuevo, ['aprobada', 'rechazada'], true) && ! Auth::user()->hasRole('admin')) {
            abort(403, 'Solo el administrador puede aprobar o rechazar cotizaciones.');
        }

        if ($nuevo === 'aprobada') {
            try {
                $cotizacion->aprobar(); // descuenta stock (atómico)
            } catch (\RuntimeException $e) {
                return back()->with('error', "No se pudo aprobar: {$e->getMessage()}");
            }
            $msg = "Cotización {$cotizacion->numero} aprobada. Stock descontado.";
        } elseif ($nuevo === 'rechazada' && $cotizacion->stock_descontado) {
            // Rechazar una cotización aprobada: revertir stock (movimientos de entrada) y limpiar flag.
            $cotizacion->revertirStock();
            $cotizacion->update(['estado' => 'rechazada']);
            $msg = "Cotización {$cotizacion->numero} rechazada. Stock repuesto.";
        } else {
            // Bloquear transiciones inválidas desde aprobada (excepto la reversión anterior).
            if ($cotizacion->estado === 'aprobada' && $nuevo !== 'aprobada') {
                return back()->with('error', 'No puedes cambiar el estado de una cotización aprobada. Usa «rechazar» para anularla y reponer stock.');
            }
            $cotizacion->update(['estado' => $nuevo]);
            $msg = "Cotización {$cotizacion->numero} marcada como {$nuevo}.";
        }

        return back()->with('success', $msg);
    }

    /** Duplica una cotización: crea una nueva en borrador con los mismos ítems, misma fecha=hoy. */
    public function duplicar(Cotizacion $cotizacion)
    {
        $this->autorizarAcceso($cotizacion);

        // El cliente debe seguir siendo visible para el usuario que duplica
        // (por si el cliente fue reasignado a otro vendedor desde que se creó la cotización original).
        abort_unless(
            Cliente::visiblesPara(Auth::user())->whereKey($cotizacion->cliente_id)->exists(),
            403,
            'El cliente de esta cotización ya no está bajo tu cuenta. Pídele al administrador que la reasigne.'
        );

        $nueva = DB::transaction(function () use ($cotizacion) {
            $cot = Cotizacion::crearConNumero([
                'cliente_id' => $cotizacion->cliente_id,
                'user_id' => Auth::id(),
                'estado' => 'borrador',
                'fecha' => now()->toDateString(),
                // Validez fija de 15 días desde hoy: predecible y evita heredar vencimientos.
                'validez' => $cotizacion->validez ? now()->addDays(15)->toDateString() : null,
                'descuento' => $cotizacion->descuento,
                'notas' => $cotizacion->notas,
            ]);

            // Copiar los ítems, respetando el precio congelado.
            foreach ($cotizacion->items as $item) {
                $cot->items()->create([
                    'producto_id' => $item->producto_id,
                    'nombre' => $item->nombre,
                    'referencia' => $item->referencia,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'subtotal' => $item->subtotal,
                ]);
            }
            $cot->recalcularTotales();

            return $cot;
        });

        return redirect()->route('cotizaciones.show', $nueva)
            ->with('success', "Cotización duplicada como {$nueva->numero} (borrador). Puedes editarla y aprobarla.");
    }

    /** PDF corporativo Sweet Go. */
    public function pdf(Cotizacion $cotizacion)
    {
        $this->autorizarAcceso($cotizacion);

        $cotizacion->load(['cliente', 'vendedor', 'items']);

        $pdf = Pdf::loadView('cotizaciones.pdf', compact('cotizacion'))->setPaper('letter');

        return $pdf->stream("Cotizacion-{$cotizacion->numero}.pdf");
    }

    /** Persiste los items congelando nombre/referencia/precio. */
    private function guardarItems(Cotizacion $cot, array $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::find($item['producto_id']);
            if (! $producto) {
                // No descartar en silencio: la validación ya pasó, pero el producto se borró en el medio.
                throw new \RuntimeException("El producto seleccionado ya no existe. Vuelve a cargar el formulario.");
            }
            $cantidad = max(1, (int) $item['cantidad']);
            $precio = isset($item['precio_unitario']) ? (float) $item['precio_unitario'] : (float) $producto->precio;

            $cot->items()->create([
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'referencia' => $producto->referencia,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $precio * $cantidad,
            ]);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fecha' => ['required', 'date'],
            'validez' => ['nullable', 'date', 'after_or_equal:fecha'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Agrega al menos un producto a la cotización.',
            'cliente_id.required' => 'Selecciona un cliente.',
        ]);

        // Tope de descuento: no puede superar el subtotal de los ítems.
        $subtotal = 0.0;
        foreach ($data['items'] as $item) {
            $precio = isset($item['precio_unitario']) && $item['precio_unitario'] !== ''
                ? (float) $item['precio_unitario']
                : (float) (\App\Models\Producto::find($item['producto_id'])?->precio ?? 0);
            $subtotal += $precio * (int) $item['cantidad'];
        }
        if (($data['descuento'] ?? 0) > $subtotal) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'descuento' => 'El descuento no puede ser mayor al subtotal ($'.number_format($subtotal, 0, ',', '.').').',
            ]);
        }

        return $data;
    }
}
