<?php

namespace App\Http\Controllers;

use App\Models\ListaPrecio;
use App\Models\PrecioProducto;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListaPrecioController extends Controller
{
    public function index()
    {
        $listas = ListaPrecio::orderByDesc('es_publica')->orderByDesc('es_predeterminada')->orderBy('nombre')->get();
        $productos = Producto::with('preciosProducto')->orderBy('nombre')->get();

        return view('listas-precios.index', compact('listas', 'productos'));
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => ['required', 'string', 'max:100']]);

        $lista = ListaPrecio::create(['nombre' => $request->nombre, 'activo' => true]);

        // Sembrar precios de la nueva lista con el precio base de cada producto.
        foreach (Producto::all() as $producto) {
            PrecioProducto::firstOrCreate(
                ['producto_id' => $producto->id, 'lista_precio_id' => $lista->id],
                ['precio' => $producto->precio]
            );
        }

        return back()->with('success', "Lista «{$lista->nombre}» creada.");
    }

    public function update(Request $request, ListaPrecio $lista)
    {
        $request->validate(['nombre' => ['required', 'string', 'max:100']]);

        DB::transaction(function () use ($request, $lista) {
            $lista->update([
                'nombre' => $request->nombre,
                'slug' => Str::slug($request->nombre),
            ]);

            // Solo una pública y una predeterminada.
            if ($request->boolean('es_publica')) {
                ListaPrecio::where('id', '!=', $lista->id)->update(['es_publica' => false]);
                $lista->update(['es_publica' => true]);
            }
            if ($request->boolean('es_predeterminada')) {
                ListaPrecio::where('id', '!=', $lista->id)->update(['es_predeterminada' => false]);
                $lista->update(['es_predeterminada' => true]);
            }
        });

        return back()->with('success', "Lista «{$lista->nombre}» actualizada.");
    }

    public function destroy(ListaPrecio $lista)
    {
        if ($lista->es_predeterminada || $lista->es_publica) {
            return back()->with('error', 'No se puede eliminar la lista pública o predeterminada. Marca otra primero.');
        }

        $lista->delete();

        return back()->with('success', 'Lista eliminada.');
    }

    /** Guarda la matriz de precios (producto x lista). */
    public function guardarPrecios(Request $request)
    {
        $precios = $request->input('precios', []); // precios[producto_id][lista_id] = valor
        $publicaId = ListaPrecio::publica()?->id;

        DB::transaction(function () use ($precios, $publicaId) {
            foreach ($precios as $productoId => $porLista) {
                foreach ($porLista as $listaId => $valor) {
                    $valor = (float) $valor;
                    PrecioProducto::updateOrCreate(
                        ['producto_id' => $productoId, 'lista_precio_id' => $listaId],
                        ['precio' => $valor]
                    );

                    // Mantener productos.precio sincronizado con la lista pública (lo usa el catálogo).
                    if ((int) $listaId === (int) $publicaId) {
                        Producto::whereKey($productoId)->update(['precio' => $valor]);
                    }
                }
            }
        });

        return back()->with('success', 'Precios actualizados.');
    }
}
