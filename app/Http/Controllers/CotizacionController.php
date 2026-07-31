<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\ListaPrecio;
use App\Models\Notificacion;
use App\Models\Producto;
use App\Models\User;
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

        // Vendedores para el selector "Asignar a vendedor" (solo lo usa el admin al editar).
        $vendedores = Auth::user()->hasRole('admin')
            ? User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'vendedor']))
                ->orderBy('name')->get(['id', 'name'])->values()
            : collect();

        return [
            'clientes' => $clientes,
            'productos' => $productos,
            'clientesLista' => $clientes->mapWithKeys(fn ($c) => [$c->id => $c->lista_precio_id])->toArray(),
            'listasNombres' => ListaPrecio::pluck('nombre', 'id')->toArray(),
            'predeterminadaId' => ListaPrecio::predeterminada()?->id,
            'vendedores' => $vendedores,
        ];
    }

    public function index(Request $request)
    {
        $u = Auth::user();

        $cotizaciones = Cotizacion::query()
            ->with(['cliente', 'vendedor', 'envio'])
            ->when(! $u->hasRole('admin'), fn ($q) => $q->where('user_id', $u->id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('estado_envio'), function ($q) use ($request) {
                if ($request->estado_envio === 'sin_envio') {
                    $q->whereDoesntHave('envio');
                } else {
                    $q->whereHas('envio', fn ($e) => $e->where('estado', $request->estado_envio));
                }
            })
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

        // Lista de vendedores para el mini-form de reasignar (solo si es admin).
        $vendedores = Auth::user()->hasRole('admin')
            ? User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'vendedor']))
                ->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('cotizaciones.show', compact('cotizacion', 'vendedores'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        $this->autorizarAcceso($cotizacion);

        // Regla jul-2026: admin edita en cualquier borrador; vendedor edita solo su propio borrador.
        if (! $cotizacion->puedeEditar(Auth::user())) {
            return redirect()->route('cotizaciones.show', $cotizacion)
                ->with('error', 'Esta cotización ya no se puede editar (ya fue enviada, cambió de estado o tiene pagos).');
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

        // Regla jul-2026: admin edita en cualquier borrador; vendedor edita solo su propio borrador.
        if (! $cotizacion->puedeEditar(Auth::user())) {
            return redirect()->route('cotizaciones.show', $cotizacion)
                ->with('error', 'Esta cotización ya no se puede editar (ya fue enviada, cambió de estado o tiene pagos).');
        }

        $data = $this->validated($request);

        // También al actualizar: el cliente debe ser suyo.
        abort_unless(
            Cliente::visiblesPara(Auth::user())->whereKey($data['cliente_id'])->exists(),
            403,
            'No puedes asignar esta cotización a un cliente ajeno.'
        );

        // Reasignación de vendedor: solo admin puede, y el destino debe tener rol admin/vendedor.
        $updates = [
            'cliente_id' => $data['cliente_id'],
            'fecha' => $data['fecha'],
            'validez' => $data['validez'] ?? null,
            'descuento' => $data['descuento'] ?? 0,
            'notas' => $data['notas'] ?? null,
        ];
        if (Auth::user()->hasRole('admin') && ! empty($data['user_id'])) {
            $candidato = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'vendedor']))
                ->whereKey($data['user_id'])->first();
            if ($candidato) {
                $updates['user_id'] = $candidato->id;
            }
        }

        // Snapshot para el diff que le mandamos al admin en la alerta.
        $antesItems = $cotizacion->items->map(fn ($i) => [
            'producto_id' => (int) $i->producto_id,
            'nombre' => $i->nombre,
            'cantidad' => (int) $i->cantidad,
            'precio_unitario' => (float) $i->precio_unitario,
        ])->all();
        $antesTotal = (float) $cotizacion->total;
        $antesNotas = (string) $cotizacion->notas;

        $estadoAntes = $cotizacion->estado;

        try {
        [$movimientosStock, $estadoRecalculado] = DB::transaction(function () use ($cotizacion, $data, $updates, $antesItems) {
            $cotizacion->update($updates);

            $cotizacion->items()->delete();
            $this->guardarItems($cotizacion, $data['items']);
            $cotizacion->recalcularTotales();
            $cotizacion->refresh()->load('items');

            // Si la cotización ya había descontado stock (aprobada/pagada), ajustamos el delta
            // por producto: reponer lo que se quitó y descontar lo que se agregó, atómico.
            $itemsDespues = $cotizacion->items->map(fn ($i) => ['producto_id' => (int) $i->producto_id, 'cantidad' => (int) $i->cantidad])->all();
            $itemsAntesSimple = collect($antesItems)->map(fn ($i) => ['producto_id' => (int) $i['producto_id'], 'cantidad' => (int) $i['cantidad']])->all();
            $movs = $cotizacion->ajustarStockPorEdicion($itemsAntesSimple, $itemsDespues);

            // Recalculamos el estado (por si el nuevo total no cuadra con los pagos aprobados).
            $prev = $cotizacion->recalcularEstadoPorEdicion();

            return [$movs, $prev];
        });
        } catch (\RuntimeException $e) {
            return redirect()->route('cotizaciones.edit', $cotizacion)
                ->withInput()
                ->with('error', 'No se pudo actualizar: ' . $e->getMessage());
        }

        // Regla jul-2026: si el que editó es un vendedor (no admin) y la cotización está en borrador,
        // avisar al admin qué cambió y el estado de pago actual.
        if (! Auth::user()->hasRole('admin')) {
            $cotizacion->refresh()->load('items');
            $this->notificarEdicionVendedor($cotizacion, $antesItems, $antesTotal, $antesNotas);
        }

        // Mensaje flash: describe stock ajustado + cambio de estado (si hubo).
        $mensaje = "Cotización {$cotizacion->numero} actualizada.";
        if (! empty($movimientosStock)) {
            $mensaje .= ' Stock ajustado: ' . implode(', ', $movimientosStock) . '.';
        }
        if ($estadoRecalculado) {
            $mensaje .= " Estado pasó de «{$estadoRecalculado}» a «{$cotizacion->fresh()->estadoLabel()}» por el ajuste del total.";
        }

        return redirect()->route('cotizaciones.show', $cotizacion)->with('success', $mensaje);
    }

    /**
     * Arma el diff (agregados / removidos / cambios de cant/precio + delta total + notas + estado pago)
     * y notifica a todos los admins.
     */
    private function notificarEdicionVendedor(Cotizacion $cot, array $antesItems, float $antesTotal, string $antesNotas): void
    {
        $antes = collect($antesItems)->keyBy('producto_id');
        $despues = $cot->items->keyBy('producto_id')->map(fn ($i) => [
            'producto_id' => (int) $i->producto_id,
            'nombre' => $i->nombre,
            'cantidad' => (int) $i->cantidad,
            'precio_unitario' => (float) $i->precio_unitario,
        ]);

        $agregados = $despues->diffKeys($antes)->values();
        $removidos = $antes->diffKeys($despues)->values();
        $modificados = $despues->intersectByKeys($antes)->filter(function ($nuevo, $pid) use ($antes) {
            $viejo = $antes[$pid];
            return $viejo['cantidad'] !== $nuevo['cantidad'] || (float) $viejo['precio_unitario'] !== (float) $nuevo['precio_unitario'];
        })->values();

        $lineas = [];
        foreach ($agregados as $it) {
            $lineas[] = "➕ Agregó «{$it['nombre']}» ×{$it['cantidad']} a $" . number_format($it['precio_unitario'], 0, ',', '.');
        }
        foreach ($removidos as $it) {
            $lineas[] = "➖ Quitó «{$it['nombre']}» (era ×{$it['cantidad']})";
        }
        foreach ($modificados as $it) {
            $viejo = $antes[$it['producto_id']];
            $cambios = [];
            if ($viejo['cantidad'] !== $it['cantidad']) {
                $cambios[] = "cantidad {$viejo['cantidad']} → {$it['cantidad']}";
            }
            if ((float) $viejo['precio_unitario'] !== (float) $it['precio_unitario']) {
                $cambios[] = 'precio $' . number_format($viejo['precio_unitario'], 0, ',', '.') . ' → $' . number_format($it['precio_unitario'], 0, ',', '.');
            }
            $lineas[] = "✏️ «{$it['nombre']}»: " . implode(', ', $cambios);
        }
        if ($antesNotas !== (string) $cot->notas) {
            $lineas[] = '📝 Actualizó observaciones';
        }

        $deltaTotal = (float) $cot->total - $antesTotal;
        if (abs($deltaTotal) >= 1) {
            $signo = $deltaTotal > 0 ? '+' : '−';
            $lineas[] = "💰 Total: $" . number_format($antesTotal, 0, ',', '.') . ' → $' . number_format($cot->total, 0, ',', '.') . " ({$signo}$" . number_format(abs($deltaTotal), 0, ',', '.') . ')';
        }

        if (empty($lineas)) {
            $lineas[] = 'Guardó cambios menores sin impacto en ítems, total ni observaciones.';
        }

        // Estado de pago actual
        $aprobado = (float) $cot->montoPagadoAprobado();
        $totalCot = (float) $cot->total;
        if ($aprobado <= 0) {
            $estadoPago = '💳 Estado de pago: SIN pagos registrados aún.';
        } elseif ($aprobado >= $totalCot) {
            $estadoPago = '💳 Estado de pago: PAGADA en su totalidad ($' . number_format($aprobado, 0, ',', '.') . ').';
        } else {
            $saldo = $totalCot - $aprobado;
            $estadoPago = '💳 Estado de pago: PARCIAL — abonado $' . number_format($aprobado, 0, ',', '.') . ', saldo pendiente $' . number_format($saldo, 0, ',', '.') . '.';
        }

        $vendedorNombre = Auth::user()->name;
        $titulo = "{$cot->numero} · editada por {$vendedorNombre}";
        $mensaje = implode("\n", $lineas) . "\n\n" . $estadoPago;

        Notificacion::alertarAdmins(
            'cotizacion_editada',
            $titulo,
            $mensaje,
            route('cotizaciones.show', $cot)
        );
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

        // Solo admin puede cambiar el estado de una cotización (decisión del cliente).
        abort_unless(Auth::user()->hasRole('admin'), 403, 'Solo el administrador puede cambiar el estado de cotizaciones.');

        $request->validate(['estado' => ['required', 'in:enviada,aprobada,rechazada']]);
        $nuevo = $request->estado;

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

    /**
     * Reasignar el vendedor de una cotización. Funciona en CUALQUIER estado
     * (incluso aprobada o con pagos): solo cambia user_id, no toca items ni stock.
     * Solo admin puede llamarlo. Registrado en bitácora por el trait RegistraBitacora.
     */
    public function reasignarVendedor(Request $request, Cotizacion $cotizacion)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403, 'Solo el administrador puede reasignar el vendedor.');

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ], [
            'user_id.required' => 'Selecciona un vendedor.',
        ]);

        // El destino debe tener rol admin o vendedor.
        $destino = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'vendedor']))
            ->whereKey($data['user_id'])->first();

        abort_unless($destino, 422, 'El usuario elegido no puede ser vendedor de cotizaciones.');

        if ($cotizacion->user_id === $destino->id) {
            return back()->with('success', 'El vendedor ya estaba asignado a ' . $destino->name . '.');
        }

        $anterior = $cotizacion->vendedor?->name ?? 'sin asignar';
        $cotizacion->update(['user_id' => $destino->id]);

        return back()->with('success', "Vendedor cambiado: {$anterior} → {$destino->name}.");
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
            'user_id' => ['nullable', 'exists:users,id'], // vendedor asignado (solo admin puede mandarlo)
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
