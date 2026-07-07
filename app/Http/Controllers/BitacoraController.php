<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        // El acceso está restringido a admin por el middleware role: en routes/web.php.
        // Defensa en profundidad (cinturón + tirantes) por si algún día la ruta se mueve fuera del grupo:
        abort_unless($request->user()?->hasRole('admin'), 403, 'Solo el administrador puede ver la bitácora.');

        $bitacoras = Bitacora::query()
            ->with('user')
            ->when($request->filled('usuario'), fn ($q) => $q->where('user_id', $request->usuario))
            ->when($request->filled('accion'), fn ($q) => $q->where('accion', $request->accion))
            ->when($request->filled('modelo'), fn ($q) => $q->where('modelo', $request->modelo))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('created_at', '<=', $request->hasta))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $usuarios = User::orderBy('name')->get(['id', 'name']);
        $acciones = Bitacora::query()->distinct()->orderBy('accion')->pluck('accion');
        $modelos = Bitacora::query()->whereNotNull('modelo')->distinct()->orderBy('modelo')->pluck('modelo');

        return view('bitacora.index', compact('bitacoras', 'usuarios', 'acciones', 'modelos'));
    }
}
