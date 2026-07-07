<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::withCount('productos')->orderBy('nombre')->get();

        return view('categorias.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        Categoria::create([
            'nombre' => $request->nombre,
            'activo' => true,
        ]);

        return back()->with('success', 'Categoría creada.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $categoria->update([
            'nombre' => $request->nombre,
            'slug' => \Illuminate\Support\Str::slug($request->nombre),
            'activo' => $request->boolean('activo'),
        ]);

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) {
            return back()->with('error', 'No se puede eliminar: la categoría tiene productos asociados.');
        }

        $categoria->delete();

        return back()->with('success', 'Categoría eliminada.');
    }
}
