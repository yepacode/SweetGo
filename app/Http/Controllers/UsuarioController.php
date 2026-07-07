<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Garantia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with('roles')->orderBy('name')->paginate(15);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name');

        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'rol' => ['required', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole($data['rol']);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario «{$user->name}» creado como {$data['rol']}.");
    }

    public function edit(User $usuario)
    {
        $roles = Role::orderBy('name')->pluck('name');

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'rol' => ['required', 'exists:roles,name'],
        ]);

        // No permitir que el único admin se quite a sí mismo el rol admin.
        if ($usuario->id === Auth::id() && $usuario->hasRole('admin') && $data['rol'] !== 'admin' && $this->esUltimoAdmin($usuario)) {
            return back()->with('error', 'No puedes quitarte el rol de administrador siendo el único admin.');
        }

        $usuario->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        if (! empty($data['password'])) {
            $usuario->update(['password' => Hash::make($data['password'])]);
        }
        $usuario->syncRoles([$data['rol']]);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario «{$usuario->name}» actualizado.");
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }
        if ($usuario->hasRole('admin') && $this->esUltimoAdmin($usuario)) {
            return back()->with('error', 'No puedes eliminar al único administrador.');
        }

        $nombre = $usuario->name;

        // Reasignar todos los recursos del usuario al admin que ejecuta la acción,
        // así no quedan registros huérfanos accesibles por otros vendedores.
        DB::transaction(function () use ($usuario) {
            $nuevoDueno = Auth::id();
            Cliente::where('user_id', $usuario->id)->update(['user_id' => $nuevoDueno]);
            Cotizacion::where('user_id', $usuario->id)->update(['user_id' => $nuevoDueno]);
            Garantia::where('user_id', $usuario->id)->update(['user_id' => $nuevoDueno]);
            $usuario->delete();
        });

        return redirect()->route('usuarios.index')->with('success', "Usuario «{$nombre}» eliminado. Sus clientes, cotizaciones y garantías se reasignaron a ti.");
    }

    /** Genera una contraseña temporal y la asigna al usuario. Muestra el valor al admin (única vez). */
    public function resetPassword(User $usuario)
    {
        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'Para cambiar tu propia contraseña, usa "Mi perfil".');
        }

        $nueva = Str::password(10, true, true, false, false); // 10 chars, letras + dígitos, sin símbolos
        $usuario->update(['password' => Hash::make($nueva)]);

        return back()->with('success', "Contraseña de «{$usuario->name}» restablecida a: {$nueva} (cópiala ahora, no volverá a mostrarse).");
    }

    private function esUltimoAdmin(User $usuario): bool
    {
        return User::role('admin')->where('id', '!=', $usuario->id)->doesntExist();
    }
}
