@extends('layouts.admin')

@section('title', 'Bitácora de movimientos')

@section('content')
    <div class="mb-6">
        <a href="{{ route('stock.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a inventario</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Bitácora de movimientos</h2>
    </div>

    <form method="GET" class="flex gap-3 mb-5">
        <select name="tipo" onchange="this.form.submit()" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Todos los tipos</option>
            <option value="entrada" @selected(request('tipo')==='entrada')>Entradas</option>
            <option value="salida" @selected(request('tipo')==='salida')>Salidas</option>
            <option value="ajuste" @selected(request('tipo')==='ajuste')>Ajustes</option>
        </select>
    </form>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium">Fecha</th>
                    <th class="px-6 py-3 font-medium">Producto</th>
                    <th class="px-6 py-3 font-medium">Tipo</th>
                    <th class="px-6 py-3 font-medium text-center">Cantidad</th>
                    <th class="px-6 py-3 font-medium text-center">Stock nuevo</th>
                    <th class="px-6 py-3 font-medium">Motivo</th>
                    <th class="px-6 py-3 font-medium">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($movimientos as $m)
                    <tr class="hover:bg-sweetgo-pink-light/30">
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-3">
                            <a href="{{ route('stock.kardex', $m->producto) }}" class="font-medium text-gray-800 hover:text-sweetgo-pink">
                                {{ $m->producto?->nombre ?? '—' }}
                            </a>
                        </td>
                        <td class="px-6 py-3"><x-mov-badge :tipo="$m->tipo" /></td>
                        <td class="px-6 py-3 text-center font-medium">{{ $m->cantidad }}</td>
                        <td class="px-6 py-3 text-center">{{ $m->stock_nuevo }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $m->motivo ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $m->user?->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">Sin movimientos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $movimientos->links() }}</div>
@endsection
