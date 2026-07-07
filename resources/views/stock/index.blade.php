@extends('layouts.admin')

@section('title', 'Inventario')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Inventario</h2>
            <p class="text-sm text-gray-500">Control de stock · una bodega</p>
        </div>
        <a href="{{ route('stock.movimientos') }}"
           class="px-4 py-2 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-sm font-medium hover:bg-sweetgo-turquoise-light transition">
            Bitácora de movimientos
        </a>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-5">
            <p class="text-sm text-gray-500">Productos</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $productos->total() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-5">
            <p class="text-sm text-gray-500">Unidades en stock</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ number_format($totalUnidades, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-5">
            <p class="text-sm text-gray-500">Alertas de stock bajo</p>
            <p class="mt-1 text-2xl font-bold {{ $stockBajo > 0 ? 'text-red-500' : 'text-gray-800' }}">{{ $stockBajo }}</p>
        </div>
    </div>

    {{-- Filtro --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar producto…"
               class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="estado" value="stock_bajo" @checked(request('estado')==='stock_bajo')
                   onchange="this.form.submit()" class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink">
            Solo stock bajo
        </label>
        <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700">Buscar</button>
    </form>

    <div x-data="{ open: false, prod: null, nombre: '', stock: 0, action: '' }"
         class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 font-medium">Referencia</th>
                        <th class="px-4 py-3 font-medium text-center">Stock actual</th>
                        <th class="px-4 py-3 font-medium text-center">Mínimo</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($productos as $p)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $p->nombre }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $p->referencia ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                    'bg-red-100 text-red-600' => $p->stock_bajo,
                                    'bg-gray-100 text-gray-700' => !$p->stock_bajo,
                                ])>{{ $p->stock_actual }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $p->stock_minimo }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="open = true; action = '{{ route('stock.movimiento', $p) }}'; nombre = @js($p->nombre); stock = {{ $p->stock_actual }}"
                                        class="px-3 py-1.5 rounded-lg bg-sweetgo-pink text-white text-xs font-medium hover:opacity-90">
                                        Movimiento
                                    </button>
                                    <a href="{{ route('stock.kardex', $p) }}" class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-500 text-xs hover:bg-gray-50">Kardex</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Sin productos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal de movimiento --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="open = false" class="absolute inset-0 bg-black/40"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Registrar movimiento</h3>
                <p class="text-sm text-gray-500 mb-4" x-text="nombre + ' · stock actual: ' + stock"></p>

                <form :action="action" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Tipo</label>
                        <select name="tipo" class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                            <option value="entrada">Entrada (sumar)</option>
                            <option value="salida">Salida (restar)</option>
                            <option value="ajuste">Ajuste (fijar stock final)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Cantidad</label>
                        <input type="number" name="cantidad" min="0" value="1" required
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Motivo (opcional)</label>
                        <input type="text" name="motivo" placeholder="Compra, corrección, merma…"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="open = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</button>
                        <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $productos->links() }}</div>

    @if ($errors->any())
        <div class="fixed bottom-4 right-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm shadow-lg">
            {{ $errors->first() }}
        </div>
    @endif
@endsection
