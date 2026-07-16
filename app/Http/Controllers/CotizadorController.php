<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\ListaPrecio;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Cotizador tipo PDV: paso 1 elegir cliente, paso 2 catálogo + carrito con precios
 * según la lista del cliente. Persiste como Cotizacion en estado "borrador".
 */
class CotizadorController extends Controller
{
    public function index()
    {
        $u = Auth::user();

        $clientes = Cliente::query()
            ->where('activo', true)
            ->visiblesPara($u)
            ->with(['listaPrecio', 'telefonos', 'emails', 'sucursales'])
            ->orderBy('nombre')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'tipo_documento' => $c->tipo_documento,
                'documento' => $c->documento,
                'telefono' => $c->telefono,
                'email' => $c->email,
                'direccion' => $c->direccion,
                'ciudad' => $c->ciudad,
                'lista_precio_id' => $c->lista_precio_id ?? ListaPrecio::predeterminada()?->id,
                'lista_nombre' => $c->listaPrecio?->nombre ?? ListaPrecio::predeterminada()?->nombre ?? '—',
                'telefonos' => $c->telefonos->map(fn ($t) => ['etiqueta' => $t->etiqueta, 'numero' => $t->numero])->values(),
                'emails' => $c->emails->map(fn ($e) => ['etiqueta' => $e->etiqueta, 'email' => $e->email])->values(),
                'sucursales' => $c->sucursales->map(fn ($s) => [
                    'nombre' => $s->nombre, 'direccion' => $s->direccion, 'ciudad' => $s->ciudad,
                    'telefono' => $s->telefono, 'contacto' => $s->contacto, 'es_principal' => (bool) $s->es_principal,
                ])->values(),
            ])->values();

        $productos = Producto::query()
            ->where('activo', true)
            ->with(['categoria', 'preciosProducto'])
            ->orderBy('nombre')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'referencia' => $p->referencia,
                'descripcion' => $p->descripcion,
                'precio_base' => (float) $p->precio,
                'categoria' => $p->categoria?->nombre,
                'categoria_id' => $p->categoria_id,
                'imagen' => $p->imagen ? Storage::url($p->imagen) : null,
                'stock' => (int) $p->stock_actual,
                'stock_minimo' => (int) $p->stock_minimo,
                'stock_maximo' => $p->stock_maximo,
                'precios' => $p->preciosProducto->mapWithKeys(fn ($pp) => [(int) $pp->lista_precio_id => (float) $pp->precio]),
            ])->values();

        $categorias = Categoria::orderBy('nombre')->get(['id', 'nombre']);
        $listasPrecios = ListaPrecio::where('activo', true)
            ->orderByDesc('es_publica')->orderByDesc('es_predeterminada')->orderBy('nombre')
            ->get(['id', 'nombre', 'es_publica', 'es_predeterminada']);
        $esAdmin = $u->hasRole('admin');

        return view('catalogo.index', compact('clientes', 'productos', 'categorias', 'listasPrecios', 'esAdmin'));
    }

    public function store(Request $request)
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
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Agrega al menos un producto al carrito antes de generar la cotización.',
            'cliente_id.required' => 'Selecciona un cliente.',
        ]);

        // Un vendedor no puede cotizar para un cliente que no es suyo.
        abort_unless(
            Cliente::visiblesPara(Auth::user())->whereKey($data['cliente_id'])->exists(),
            403,
            'No puedes crear una cotización para un cliente ajeno.'
        );

        // Si el usuario NO es admin, los precios se re-calculan según la lista del cliente
        // (evita que un vendedor "hackee" el input del carrito y cotice bajo el precio real).
        if (! Auth::user()->hasRole('admin')) {
            $cliente = Cliente::with('listaPrecio')->find($data['cliente_id']);
            $listaId = $cliente->lista_precio_id ?? ListaPrecio::predeterminada()?->id;
            foreach ($data['items'] as $i => $item) {
                $producto = Producto::with('preciosProducto')->find($item['producto_id']);
                if (! $producto) continue;
                $data['items'][$i]['precio_unitario'] = (float) $producto->precioEnLista($listaId);
            }
        }

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

            foreach ($data['items'] as $item) {
                $producto = Producto::find($item['producto_id']);
                if (! $producto) {
                    throw new \RuntimeException('Uno de los productos del carrito ya no existe. Vuelve a cargar el catálogo.');
                }
                $cantidad = max(1, (int) $item['cantidad']);
                $precio = (float) $item['precio_unitario'];

                $cot->items()->create([
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'referencia' => $producto->referencia,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $precio * $cantidad,
                ]);
            }

            $cot->recalcularTotales();

            return $cot;
        });

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', "Cotización {$cotizacion->numero} creada desde el catálogo.");
    }
}
