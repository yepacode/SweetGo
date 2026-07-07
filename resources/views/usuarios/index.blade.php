@extends('layouts.admin')

@section('title', 'Usuarios')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Usuarios</h2>
            <p class="text-sm text-gray-500">Gestiona el acceso del equipo (administradores y vendedores).</p>
        </div>
        <a href="{{ route('usuarios.create') }}"
           class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90 transition">
            + Nuevo usuario
        </a>
    </div>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Correo</th>
                        <th class="px-4 py-3 font-medium">Rol</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($usuarios as $u)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $u->name }}
                                @if ($u->id === auth()->id())<span class="ml-1 text-xs text-gray-400">(tú)</span>@endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                @foreach ($u->roles as $rol)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $rol->name === 'admin' ? 'bg-sweetgo-pink-light text-sweetgo-pink' : 'bg-sweetgo-turquoise-light text-teal-700' }}">{{ ucfirst($rol->name) }}</span>
                                @endforeach
                                @if ($u->roles->isEmpty())<span class="text-xs text-gray-400">Sin rol</span>@endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3 text-xs">
                                    <a href="{{ route('usuarios.edit', $u) }}" class="text-sweetgo-turquoise hover:underline">Editar</a>
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('usuarios.reset-password', $u) }}" onsubmit="return confirm('¿Restablecer la contraseña de «{{ $u->name }}»? Se generará una temporal.')">
                                            @csrf
                                            <button class="text-gray-500 hover:text-gray-700 hover:underline">Reset contraseña</button>
                                        </form>
                                        <form method="POST" action="{{ route('usuarios.destroy', $u) }}" onsubmit="return confirm('¿Eliminar a «{{ $u->name }}»? Sus registros pasarán a ti.')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-400 hover:text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No hay usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $usuarios->links() }}</div>
@endsection
