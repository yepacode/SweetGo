<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaProductosExport;
use App\Imports\ProductosImport;
use App\Models\Categoria;
use App\Models\ListaPrecio;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $productos = Producto::query()
            ->with('categoria')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(function ($sub) use ($b) {
                    $sub->where('nombre', 'like', "%{$b}%")
                        ->orWhere('referencia', 'like', "%{$b}%");
                });
            })
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria_id', $request->categoria))
            ->when($request->estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($request->estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->when($request->estado === 'stock_bajo', fn ($q) => $q->whereColumn('stock_actual', '<=', 'stock_minimo')->where('stock_minimo', '>', 0))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        $categorias = Categoria::orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'categorias'));
    }

    public function create()
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $listas = ListaPrecio::orderByDesc('es_publica')->orderByDesc('es_predeterminada')->orderBy('nombre')->get();
        $preciosActuales = []; // producto nuevo → aún sin precios por lista

        return view('productos.create', compact('categorias', 'listas', 'preciosActuales'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $stockInicial = (int) $request->input('stock_actual', 0);
        $preciosLista = $request->input('precios_lista', []);
        unset($data['stock_actual'], $data['precios_lista']); // el stock se maneja vía movimiento

        $producto = Producto::create($data + ['stock_actual' => 0]);

        // Sembrar precio por lista: valor del form si viene, sino precio base.
        foreach (ListaPrecio::all() as $lista) {
            $valor = $preciosLista[$lista->id] ?? null;
            $precio = ($valor !== null && $valor !== '') ? (float) $valor : (float) $producto->precio;
            $producto->preciosProducto()->create(['lista_precio_id' => $lista->id, 'precio' => $precio]);
        }
        $this->sincronizarPrecioBaseConPublica($producto, $preciosLista);

        if ($stockInicial > 0) {
            $producto->registrarMovimiento('entrada', $stockInicial, 'Stock inicial');
        }

        return redirect()->route('productos.index')
            ->with('success', "Producto «{$producto->nombre}» creado correctamente.");
    }

    public function show(Producto $producto)
    {
        $producto->load('categoria');
        $movimientos = $producto->movimientos()->with('user')->latest()->paginate(10);

        return view('productos.show', compact('producto', 'movimientos'));
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $listas = ListaPrecio::orderByDesc('es_publica')->orderByDesc('es_predeterminada')->orderBy('nombre')->get();
        $preciosActuales = $producto->preciosProducto()->pluck('precio', 'lista_precio_id')->all();

        return view('productos.edit', compact('producto', 'categorias', 'listas', 'preciosActuales'));
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $preciosLista = $request->input('precios_lista', []);
        unset($data['stock_actual'], $data['precios_lista']); // el stock no se edita aquí, se ajusta en inventario
        $producto->update($data);

        // Actualizar precio en cada lista con el valor enviado (o precio base si viene vacío).
        foreach (ListaPrecio::all() as $lista) {
            $valor = $preciosLista[$lista->id] ?? null;
            $precio = ($valor !== null && $valor !== '') ? (float) $valor : (float) $producto->precio;
            $producto->preciosProducto()->updateOrCreate(
                ['lista_precio_id' => $lista->id],
                ['precio' => $precio]
            );
        }
        $this->sincronizarPrecioBaseConPublica($producto, $preciosLista);

        return redirect()->route('productos.index')
            ->with('success', "Producto «{$producto->nombre}» actualizado.");
    }

    /**
     * Si el usuario definió un precio para la lista pública, ese valor manda sobre productos.precio
     * (para mantener consistencia con el catálogo público).
     */
    private function sincronizarPrecioBaseConPublica(Producto $producto, array $preciosLista): void
    {
        $publica = ListaPrecio::publica();
        if (! $publica) {
            return;
        }
        $valor = $preciosLista[$publica->id] ?? null;
        if ($valor !== null && $valor !== '' && (float) $valor !== (float) $producto->precio) {
            $producto->update(['precio' => (float) $valor]);
        }
    }

    public function destroy(Producto $producto)
    {
        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }
        $nombre = $producto->nombre;
        $producto->delete();

        return redirect()->route('productos.index')
            ->with('success', "Producto «{$nombre}» eliminado.");
    }

    /** Procesa la importación masiva desde Excel/CSV. */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ], [], ['archivo' => 'archivo']);

        $import = new ProductosImport();
        Excel::import($import, $request->file('archivo'));

        return redirect()->route('productos.index')->with(
            'success',
            "Importación completada: {$import->creados} creados, {$import->actualizados} actualizados"
            . ($import->omitidos ? ", {$import->omitidos} omitidos (sin nombre)" : '') . '.'
        );
    }

    /** Descarga la plantilla XLSX (formato nativo Excel con branding Sweet Go). */
    public function plantilla()
    {
        return Excel::download(new PlantillaProductosExport(), 'plantilla_productos_sweetgo.xlsx');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock_actual' => ['nullable', 'integer', 'min:0'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'stock_maximo' => ['nullable', 'integer', 'min:0', 'gte:stock_minimo'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'activo' => ['nullable', 'boolean'],
            'precios_lista' => ['nullable', 'array'],
            'precios_lista.*' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'categoria_id' => 'categoría',
            'stock_actual' => 'stock inicial',
            'stock_minimo' => 'stock mínimo',
            'stock_maximo' => 'stock máximo',
            'precios_lista.*' => 'precio por lista',
        ]);
    }
}
