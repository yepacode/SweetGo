@extends('layouts.admin')

@section('title', 'Clientes')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Clientes</h2>
            <p class="text-sm text-gray-500">{{ $clientes->total() }} clientes registrados</p>
        </div>
        <a href="{{ route('clientes.create') }}"
           class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90 transition">
            + Nuevo cliente
        </a>
    </div>

    <form method="GET" class="mb-5">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar nombre, documento, teléfono o correo…"
               class="w-full sm:w-96 rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
    </form>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Documento</th>
                        <th class="px-4 py-3 font-medium">Contacto</th>
                        <th class="px-4 py-3 font-medium">Ciudad</th>
                        <th class="px-4 py-3 font-medium text-center">Cotizaciones</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($clientes as $c)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('clientes.show', $c) }}" class="font-medium text-gray-800 hover:text-sweetgo-pink">{{ $c->nombre }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $c->tipo_documento }} {{ $c->documento ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $c->telefono ?? '—' }}
                                @if ($c->email)<br><span class="text-xs">{{ $c->email }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $c->ciudad ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-sweetgo-turquoise-light text-teal-700 text-xs">{{ $c->cotizaciones_count }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3 text-xs">
                                    <a href="{{ route('clientes.show', $c) }}" class="text-gray-500 hover:underline">Ver</a>
                                    <a href="{{ route('clientes.edit', $c) }}" class="text-sweetgo-turquoise hover:underline">Editar</a>
                                    @if (auth()->user()->hasRole('admin'))
                                        <form method="POST" action="{{ route('clientes.destroy', $c) }}"
                                              onsubmit="return confirm('¿Eliminar «{{ $c->nombre }}»?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-400 hover:text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No hay clientes aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $clientes->links() }}</div>
@endsection
