@extends('layouts.admin')

@section('title', 'Kardex · '.$producto->nombre)

@section('content')
    <div class="mb-6">
        <a href="{{ route('stock.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a inventario</a>
        <div class="flex items-center justify-between mt-1">
            <h2 class="text-xl font-semibold text-gray-800">Kardex: {{ $producto->nombre }}</h2>
            <span class="px-3 py-1 rounded-full bg-sweetgo-pink-light text-sweetgo-pink text-sm font-medium">
                Stock actual: {{ $producto->stock_actual }}
            </span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium">Fecha</th>
                    <th class="px-6 py-3 font-medium">Tipo</th>
                    <th class="px-6 py-3 font-medium text-center">Cantidad</th>
                    <th class="px-6 py-3 font-medium text-center">Anterior</th>
                    <th class="px-6 py-3 font-medium text-center">Nuevo</th>
                    <th class="px-6 py-3 font-medium">Motivo</th>
                    <th class="px-6 py-3 font-medium">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($movimientos as $m)
                    <tr class="hover:bg-sweetgo-pink-light/30">
                        <td class="px-6 py-3 text-gray-500">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-3"><x-mov-badge :tipo="$m->tipo" /></td>
                        <td class="px-6 py-3 text-center font-medium">{{ $m->cantidad }}</td>
                        <td class="px-6 py-3 text-center text-gray-400">{{ $m->stock_anterior }}</td>
                        <td class="px-6 py-3 text-center font-semibold text-gray-800">{{ $m->stock_nuevo }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $m->motivo ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $m->user?->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">Sin movimientos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $movimientos->links() }}</div>
@endsection
