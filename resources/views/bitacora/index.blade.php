@extends('layouts.admin')

@section('title', 'Bitácora')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Bitácora de actividad</h2>
        <p class="text-sm text-gray-500">Registro de las acciones realizadas por los usuarios en la plataforma.</p>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-xl border border-sweetgo-pink-light p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <select name="usuario" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Todos los usuarios</option>
            @foreach ($usuarios as $u)
                <option value="{{ $u->id }}" @selected(request('usuario') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <select name="accion" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Toda acción</option>
            @foreach ($acciones as $a)
                <option value="{{ $a }}" @selected(request('accion') === $a)>{{ ucfirst($a) }}</option>
            @endforeach
        </select>
        <select name="modelo" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Todo módulo</option>
            @foreach ($modelos as $m)
                <option value="{{ $m }}" @selected(request('modelo') === $m)>{{ $m }}</option>
            @endforeach
        </select>
        <input type="date" name="desde" value="{{ request('desde') }}" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink" title="Desde">
        <input type="date" name="hasta" value="{{ request('hasta') }}" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink" title="Hasta">
        <div class="flex gap-2">
            <button class="flex-1 px-4 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700">Filtrar</button>
            <a href="{{ route('bitacora.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 font-medium">Acción</th>
                        <th class="px-4 py-3 font-medium">Detalle</th>
                        <th class="px-4 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($bitacoras as $b)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $b->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $b->user?->name ?? 'Sistema' }}</td>
                            <td class="px-4 py-3"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $b->accionBadge() }}">{{ ucfirst($b->accion) }}</span></td>
                            <td class="px-4 py-3 text-gray-700">{{ $b->descripcion }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">{{ $b->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No hay actividad registrada con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $bitacoras->links() }}</div>
@endsection
