<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ListaPrecio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    /** Autoriza: admin ve todo, vendedor solo sus propios clientes. Huérfanos (user_id null) NO son accesibles por vendedores. */
    private function autorizarAcceso(Cliente $cliente): void
    {
        $u = Auth::user();
        if (! $u->hasRole('admin') && $cliente->user_id !== $u->id) {
            abort(403, 'No tienes acceso a este cliente.');
        }
    }

    public function index(Request $request)
    {
        $clientes = Cliente::query()
            ->visiblesPara(Auth::user())
            ->withCount('cotizaciones')
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(function ($sub) use ($b) {
                    $sub->where('nombre', 'like', "%{$b}%")
                        ->orWhere('documento', 'like', "%{$b}%")
                        ->orWhere('telefono', 'like', "%{$b}%")
                        ->orWhere('email', 'like', "%{$b}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        $listas = ListaPrecio::where('activo', true)->orderBy('nombre')->get();
        $listaDefault = ListaPrecio::predeterminada()?->id;

        return view('clientes.create', compact('listas', 'listaDefault'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['lista_precio_id'] = $data['lista_precio_id'] ?? ListaPrecio::predeterminada()?->id;
        $data['user_id'] = Auth::id(); // el creador es el dueño

        $cliente = Cliente::create($data);

        return redirect()->route('clientes.show', $cliente)
            ->with('success', "Cliente «{$cliente->nombre}» creado.");
    }

    public function show(Cliente $cliente)
    {
        $this->autorizarAcceso($cliente);

        $cliente->load([
            'cotizaciones' => fn ($q) => $q->latest(),
            'garantias' => fn ($q) => $q->latest(),
        ]);

        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        $this->autorizarAcceso($cliente);

        $listas = ListaPrecio::where('activo', true)->orderBy('nombre')->get();
        $listaDefault = ListaPrecio::predeterminada()?->id;

        return view('clientes.edit', compact('cliente', 'listas', 'listaDefault'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $this->autorizarAcceso($cliente);

        $data = $this->validated($request);

        // Si el cliente estaba sin dueño (legacy huérfano), lo adopta quien lo edita.
        if ($cliente->user_id === null) {
            $data['user_id'] = Auth::id();
        }

        $cliente->update($data);

        return redirect()->route('clientes.show', $cliente)
            ->with('success', "Cliente «{$cliente->nombre}» actualizado.");
    }

    public function destroy(Cliente $cliente)
    {
        // Los clientes con historial (cotizaciones/garantías) NO se pueden borrar (RESTRICT en BD).
        if ($cliente->cotizaciones()->exists() || $cliente->garantias()->exists()) {
            return back()->with('error', "No se puede eliminar «{$cliente->nombre}»: tiene historial de cotizaciones o garantías. Márcalo como inactivo en su lugar.");
        }

        $nombre = $cliente->nombre;
        try {
            $cliente->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "No se pudo eliminar «{$nombre}»: existen registros relacionados.");
        }

        return redirect()->route('clientes.index')
            ->with('success', "Cliente «{$nombre}» eliminado.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['nullable', 'string', 'max:20'],
            'documento' => ['nullable', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'lista_precio_id' => ['nullable', 'exists:lista_precios,id'],
        ]);
    }
}
