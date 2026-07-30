<?php

namespace App\Http\Controllers;

use App\Exports\PlantillaClientesExport;
use App\Imports\ClientesImport;
use App\Models\Cliente;
use App\Models\ListaPrecio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
            ->with('vendedor')
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
        $vendedores = $this->vendedoresAsignables();

        return view('clientes.create', compact('listas', 'listaDefault', 'vendedores'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['lista_precio_id'] = $data['lista_precio_id'] ?? ListaPrecio::predeterminada()?->id;
        // Solo el admin puede asignar un vendedor distinto. Vendedores → siempre se autoasignan.
        $data['user_id'] = (Auth::user()->hasRole('admin') && ! empty($data['user_id']))
            ? (int) $data['user_id']
            : Auth::id();

        [$telefonos, $emails, $sucursales] = $this->extraerHijos($data);
        // Sincronizar campos "principal" en la tabla clientes para no romper búsqueda/legacy.
        $data['telefono'] = $telefonos[0]['numero'] ?? $data['telefono'] ?? null;
        $data['email'] = $emails[0]['email'] ?? $data['email'] ?? null;

        $cliente = DB::transaction(function () use ($data, $telefonos, $emails, $sucursales) {
            $cliente = Cliente::create($data);
            $this->sincronizarHijos($cliente, $telefonos, $emails, $sucursales);
            return $cliente;
        });

        return redirect()->route('clientes.show', $cliente)
            ->with('success', "Cliente «{$cliente->nombre}» creado.");
    }

    public function show(Cliente $cliente)
    {
        $this->autorizarAcceso($cliente);

        $cliente->load([
            'telefonos',
            'emails',
            'sucursales',
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
        $vendedores = $this->vendedoresAsignables();

        return view('clientes.edit', compact('cliente', 'listas', 'listaDefault', 'vendedores'));
    }

    /**
     * Vendedores + admins que pueden ser dueños de un cliente.
     * Solo se expone al admin (para asignar / reasignar).
     */
    private function vendedoresAsignables()
    {
        if (! Auth::user()?->hasRole('admin')) {
            return collect();
        }
        return \App\Models\User::role(['admin', 'vendedor'])->orderBy('name')->get(['id', 'name']);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $this->autorizarAcceso($cliente);

        $data = $this->validated($request);

        // Admin puede reasignar; vendedor NO puede cambiar el dueño.
        if (Auth::user()->hasRole('admin') && ! empty($data['user_id'])) {
            $data['user_id'] = (int) $data['user_id'];
        } else {
            unset($data['user_id']);
            // Si el cliente estaba sin dueño (legacy huérfano), lo adopta quien lo edita.
            if ($cliente->user_id === null) {
                $data['user_id'] = Auth::id();
            }
        }

        [$telefonos, $emails, $sucursales] = $this->extraerHijos($data);
        $data['telefono'] = $telefonos[0]['numero'] ?? $data['telefono'] ?? null;
        $data['email'] = $emails[0]['email'] ?? $data['email'] ?? null;

        DB::transaction(function () use ($cliente, $data, $telefonos, $emails, $sucursales) {
            $cliente->update($data);
            $this->sincronizarHijos($cliente, $telefonos, $emails, $sucursales);
        });

        return redirect()->route('clientes.show', $cliente)
            ->with('success', "Cliente «{$cliente->nombre}» actualizado.");
    }

    /**
     * Extrae los arrays de teléfonos/correos/sucursales del payload validado y los limpia
     * (descarta filas totalmente vacías). Los quita de $data para no filtrarlos al Cliente::create.
     */
    private function extraerHijos(array &$data): array
    {
        $telefonos = collect($data['telefonos'] ?? [])
            ->filter(fn ($t) => filled($t['numero'] ?? null))
            ->values()->all();

        $emails = collect($data['emails'] ?? [])
            ->filter(fn ($e) => filled($e['email'] ?? null))
            ->values()->all();

        $sucursales = collect($data['sucursales'] ?? [])
            ->filter(fn ($s) => filled($s['nombre'] ?? null))
            ->values()->all();

        unset($data['telefonos'], $data['emails'], $data['sucursales']);

        return [$telefonos, $emails, $sucursales];
    }

    /**
     * Reemplaza teléfonos/correos/sucursales del cliente. Sencillo delete + insert:
     * el volumen por cliente es bajo y evita divergencias por edición fila-a-fila.
     */
    private function sincronizarHijos(Cliente $cliente, array $telefonos, array $emails, array $sucursales): void
    {
        $cliente->telefonos()->delete();
        foreach ($telefonos as $i => $t) {
            $cliente->telefonos()->create([
                'etiqueta' => $t['etiqueta'] ?? null,
                'numero' => $t['numero'],
                'orden' => $i,
            ]);
        }

        $cliente->emails()->delete();
        foreach ($emails as $i => $e) {
            $cliente->emails()->create([
                'etiqueta' => $e['etiqueta'] ?? null,
                'email' => $e['email'],
                'orden' => $i,
            ]);
        }

        // Solo una sucursal principal como máximo (la primera marcada gana).
        $vistoPrincipal = false;
        $cliente->sucursales()->delete();
        foreach ($sucursales as $i => $s) {
            $esPrincipal = ! $vistoPrincipal && (($s['es_principal'] ?? '0') === '1' || ($s['es_principal'] ?? 0) === 1);
            if ($esPrincipal) {
                $vistoPrincipal = true;
            }
            $cliente->sucursales()->create([
                'nombre' => $s['nombre'],
                'direccion' => $s['direccion'] ?? null,
                'ciudad' => $s['ciudad'] ?? null,
                'telefono' => $s['telefono'] ?? null,
                'contacto' => $s['contacto'] ?? null,
                'notas' => $s['notas'] ?? null,
                'es_principal' => $esPrincipal,
                'orden' => $i,
            ]);
        }
    }

    /** Procesa la importación masiva de clientes desde Excel/CSV. Solo admin. */
    public function importar(Request $request)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:8192'],
        ], [], ['archivo' => 'archivo']);

        $import = new ClientesImport();
        Excel::import($import, $request->file('archivo'));

        return redirect()->route('clientes.index')->with(
            'success',
            "Importación completada: {$import->creados} creados, {$import->actualizados} actualizados"
            . ($import->omitidos ? ", {$import->omitidos} omitidos (sin nombre)" : '') . '.'
        );
    }

    /** Descarga la plantilla XLSX de clientes con branding Sweet Go. Solo admin. */
    public function plantilla()
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);

        return Excel::download(new PlantillaClientesExport(), 'plantilla_clientes_sweetgo.xlsx');
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
            'user_id' => ['nullable', 'exists:users,id'],  // solo se respeta si el actor es admin

            'telefonos' => ['nullable', 'array'],
            'telefonos.*.etiqueta' => ['nullable', 'string', 'max:40'],
            'telefonos.*.numero' => ['nullable', 'string', 'max:50'],

            'emails' => ['nullable', 'array'],
            'emails.*.etiqueta' => ['nullable', 'string', 'max:40'],
            'emails.*.email' => ['nullable', 'email', 'max:255'],

            'sucursales' => ['nullable', 'array'],
            'sucursales.*.nombre' => ['nullable', 'string', 'max:120'],
            'sucursales.*.direccion' => ['nullable', 'string', 'max:255'],
            'sucursales.*.ciudad' => ['nullable', 'string', 'max:100'],
            'sucursales.*.telefono' => ['nullable', 'string', 'max:50'],
            'sucursales.*.contacto' => ['nullable', 'string', 'max:120'],
            'sucursales.*.notas' => ['nullable', 'string'],
            'sucursales.*.es_principal' => ['nullable', 'in:0,1'],
        ]);
    }
}
