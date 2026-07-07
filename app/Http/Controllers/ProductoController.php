<?php

namespace App\Http\Controllers;

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

        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $stockInicial = (int) $request->input('stock_actual', 0);
        unset($data['stock_actual']); // el stock se maneja vía movimiento

        $producto = Producto::create($data + ['stock_actual' => 0]);

        // Sembrar el precio en todas las listas (= precio base al crear).
        foreach (ListaPrecio::all() as $lista) {
            $producto->preciosProducto()->create(['lista_precio_id' => $lista->id, 'precio' => $producto->precio]);
        }

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

        return view('productos.edit', compact('producto', 'categorias'));
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

        unset($data['stock_actual']); // el stock no se edita aquí, se ajusta en inventario
        $producto->update($data);

        // El precio base editado aquí actualiza la lista pública (que ve el catálogo).
        if ($publica = ListaPrecio::publica()) {
            $producto->preciosProducto()->updateOrCreate(
                ['lista_precio_id' => $publica->id],
                ['precio' => $producto->precio]
            );
        }

        return redirect()->route('productos.index')
            ->with('success', "Producto «{$producto->nombre}» actualizado. (Otros precios se ajustan en Listas de precios.)");
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

    /** Descarga una plantilla CSV para la importación. */
    public function plantilla()
    {
        $headers = ['nombre', 'referencia', 'categoria', 'precio', 'stock', 'stock_minimo'];
        $ejemplo = ['Cepillo Alpargata', '4001', 'Cepillos', '8500', '10', '5'];

        $callback = function () use ($headers, $ejemplo) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM para acentos en Excel
            fputcsv($out, $headers);
            fputcsv($out, $ejemplo);
            fclose($out);
        };

        return response()->streamDownload($callback, 'plantilla_productos_sweetgo.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
            'imagen' => ['nullable', 'image', 'max:4096'],
            'activo' => ['nullable', 'boolean'],
        ], [], [
            'categoria_id' => 'categoría',
            'stock_actual' => 'stock inicial',
            'stock_minimo' => 'stock mínimo',
        ]);
    }
}
