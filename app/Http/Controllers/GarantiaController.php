<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Garantia;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GarantiaController extends Controller
{
    private function autorizarAcceso(Garantia $garantia): void
    {
        $u = Auth::user();
        if (! $u->hasRole('admin') && $garantia->user_id !== $u->id) {
            abort(403, 'No tienes acceso a esta garantía.');
        }
    }

    public function index(Request $request)
    {
        $u = Auth::user();

        $garantias = Garantia::query()
            ->with(['cliente', 'producto'])
            ->when(! $u->hasRole('admin'), fn ($q) => $q->where('user_id', $u->id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = $request->buscar;
                $q->where(function ($sub) use ($b) {
                    $sub->where('numero', 'like', "%{$b}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$b}%"));
                });
            })
            ->orderByDesc('numero')
            ->paginate(15)
            ->withQueryString();

        $conteos = [
            'recibido' => Garantia::where('estado', 'recibido')->count(),
            'en_gestion' => Garantia::where('estado', 'en_gestion')->count(),
            'resuelto' => Garantia::where('estado', 'resuelto')->count(),
            'cerrado' => Garantia::where('estado', 'cerrado')->count(),
        ];

        return view('garantias.index', compact('garantias', 'conteos'));
    }

    public function create()
    {
        $clientes = Cliente::where('activo', true)
            ->visiblesPara(Auth::user())
            ->orderBy('nombre')->get();
        $productos = Producto::orderBy('nombre')->get(['id', 'nombre']);

        return view('garantias.create', compact('clientes', 'productos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'producto_id' => ['nullable', 'exists:productos,id'],
            'producto_nombre' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'fecha_recibido' => ['required', 'date'],
            'evidencias.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ]);

        // Un vendedor no puede registrar una garantía a un cliente ajeno.
        abort_unless(
            Cliente::visiblesPara(Auth::user())->whereKey($data['cliente_id'])->exists(),
            403,
            'No puedes registrar una garantía para un cliente ajeno.'
        );

        $garantia = Garantia::crearConNumero([
            'cliente_id' => $data['cliente_id'],
            'producto_id' => $data['producto_id'] ?? null,
            'producto_nombre' => $data['producto_nombre'] ?? null,
            'descripcion' => $data['descripcion'],
            'estado' => 'recibido',
            'user_id' => Auth::id(),
            'fecha_recibido' => $data['fecha_recibido'],
        ]);

        $this->guardarEvidencias($request, $garantia);

        return redirect()->route('garantias.show', $garantia)
            ->with('success', "Garantía {$garantia->numero} registrada.");
    }

    public function show(Garantia $garantia)
    {
        $this->autorizarAcceso($garantia);

        $garantia->load(['cliente', 'producto', 'vendedor', 'documentos']);

        return view('garantias.show', compact('garantia'));
    }

    /** Cambia el estado del flujo. Al cerrar registra fecha de cierre. */
    public function estado(Request $request, Garantia $garantia)
    {
        $this->autorizarAcceso($garantia);

        $data = $request->validate([
            'estado' => ['required', 'in:recibido,en_gestion,resuelto,cerrado'],
            'solucion' => ['nullable', 'string'],
        ]);

        $update = ['estado' => $data['estado']];

        if (! empty($data['solucion'])) {
            $update['solucion'] = $data['solucion'];
        }

        if ($data['estado'] === 'cerrado') {
            $update['fecha_cierre'] = now()->toDateString();
        } elseif ($garantia->estado === 'cerrado') {
            $update['fecha_cierre'] = null; // reabrir
        }

        $garantia->update($update);

        return back()->with('success', "Garantía {$garantia->numero}: estado «{$garantia->estado_label}».");
    }

    /** Agregar evidencias a una garantía existente. */
    public function evidencias(Request $request, Garantia $garantia)
    {
        $this->autorizarAcceso($garantia);

        $request->validate([
            'evidencias' => ['required', 'array', 'max:10'],
            'evidencias.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ]);

        $n = $this->guardarEvidencias($request, $garantia);

        return back()->with('success', "{$n} evidencia(s) adjuntada(s).");
    }

    public function destroy(Garantia $garantia)
    {
        foreach ($garantia->documentos as $doc) {
            Storage::disk('public')->delete($doc->ruta);
        }
        $numero = $garantia->numero;
        $garantia->delete();

        return redirect()->route('garantias.index')->with('success', "Garantía {$numero} eliminada.");
    }

    private function guardarEvidencias(Request $request, Garantia $garantia): int
    {
        $n = 0;
        foreach ($request->file('evidencias', []) as $archivo) {
            if (! $archivo) {
                continue;
            }
            $ruta = $archivo->store('garantias', 'public');
            $garantia->documentos()->create([
                'ruta' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'es_imagen' => str_starts_with((string) $archivo->getMimeType(), 'image/'),
                'user_id' => Auth::id(),
            ]);
            $n++;
        }

        return $n;
    }
}
