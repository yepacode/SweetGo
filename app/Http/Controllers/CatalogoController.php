<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\EnlaceCatalogo;
use App\Models\Producto;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /** Panel interno: gestión de enlaces públicos + vista previa. */
    public function index()
    {
        $enlaces = EnlaceCatalogo::latest()->get();
        $totalProductos = Producto::where('activo', true)->count();

        return view('catalogo.index', compact('enlaces', 'totalProductos'));
    }

    /** Crea un nuevo enlace público. */
    public function crearEnlace(Request $request)
    {
        $request->validate(['titulo' => ['nullable', 'string', 'max:100']]);

        EnlaceCatalogo::create([
            'titulo' => $request->titulo ?: 'Catálogo público',
            'activo' => true,
        ]);

        return back()->with('success', 'Enlace de catálogo creado.');
    }

    public function toggleEnlace(EnlaceCatalogo $enlace)
    {
        $enlace->update(['activo' => ! $enlace->activo]);

        return back()->with('success', $enlace->activo ? 'Enlace activado.' : 'Enlace desactivado.');
    }

    public function eliminarEnlace(EnlaceCatalogo $enlace)
    {
        $enlace->delete();

        return back()->with('success', 'Enlace eliminado.');
    }

    /** Vista PÚBLICA (sin login) del catálogo por token. */
    public function publico(Request $request, string $token)
    {
        // Saneamos input público en silencio (no queremos 422 en una URL pública).
        $buscar = is_string($request->buscar) ? mb_substr(trim($request->buscar), 0, 100) : null;
        $categoria = is_numeric($request->categoria) ? (int) $request->categoria : null;

        $enlace = EnlaceCatalogo::where('token', $token)->where('activo', true)->first();

        abort_if(! $enlace, 404);

        // Registrar visita en un solo UPDATE.
        $enlace->increment('visitas', 1, ['ultima_visita' => now()]);

        $categorias = Categoria::where('activo', true)
            ->whereHas('productos', fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')->get();

        $productos = Producto::query()
            ->where('activo', true)
            ->with('categoria')
            ->when($buscar, fn ($q) => $q->where(fn ($sub) => $sub->where('nombre', 'like', "%{$buscar}%")->orWhere('referencia', 'like', "%{$buscar}%")))
            // Solo aplica el filtro si la categoría existe entre las visibles (silenciosamente ignora inválidas)
            ->when($categoria && $categorias->contains('id', $categoria), fn ($q) => $q->where('categoria_id', $categoria))
            ->orderBy('nombre')
            ->get();

        $whatsapp = config('sweetgo.whatsapp');

        return view('catalogo.publico', compact('enlace', 'categorias', 'productos', 'whatsapp'));
    }
}
