@extends('layouts.admin')

@section('title', $producto->nombre)

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('productos.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a productos</a>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">{{ $producto->nombre }}</h2>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('stock.kardex', $producto) }}" class="px-4 py-2 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-sm hover:bg-sweetgo-turquoise-light">Ver kardex</a>
            <a href="{{ route('productos.edit', $producto) }}" class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm hover:opacity-90">Editar</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" @if ($producto->imagen) x-data="{ zoom: false }" @endif>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            @if ($producto->imagen)
                {{-- Miniatura grande con lupa (clic para ampliar) --}}
                <button type="button" @click="zoom = true"
                        class="group relative block w-full h-80 rounded-lg border border-gray-100 bg-white overflow-hidden cursor-zoom-in">
                    <img src="{{ Storage::url($producto->imagen) }}" alt="{{ $producto->nombre }}"
                         class="w-full h-full object-contain object-center p-3 transition-transform duration-200 group-hover:scale-105">
                    <span class="absolute bottom-2 right-2 flex items-center gap-1 rounded-full bg-black/60 text-white text-xs px-2.5 py-1 opacity-80 group-hover:opacity-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3M11 8v6M8 11h6"/>
                        </svg>
                        Ampliar
                    </span>
                </button>

                {{-- Lightbox a pantalla completa --}}
                <div x-show="zoom" x-cloak @keydown.escape.window="zoom = false" @click="zoom = false"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                     x-transition.opacity>
                    <button type="button" @click="zoom = false"
                            class="absolute top-4 right-4 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-full w-10 h-10 flex items-center justify-center text-2xl leading-none">&times;</button>
                    <img src="{{ Storage::url($producto->imagen) }}" alt="{{ $producto->nombre }}"
                         @click.stop
                         class="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl bg-white">
                </div>
            @else
                <div class="w-full h-80 rounded-lg bg-sweetgo-pink-light flex items-center justify-center text-6xl text-sweetgo-pink">✦</div>
            @endif
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div><dt class="text-gray-400">Referencia</dt><dd class="text-gray-800 font-medium">{{ $producto->referencia ?? '—' }}</dd></div>
                <div><dt class="text-gray-400">Categoría</dt><dd class="text-gray-800 font-medium">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</dd></div>
                <div><dt class="text-gray-400">Precio</dt><dd class="text-gray-800 font-medium text-lg">${{ number_format($producto->precio, 0, ',', '.') }}</dd></div>
                <div><dt class="text-gray-400">Estado</dt><dd>{!! $producto->activo ? '<span class="text-sweetgo-turquoise font-medium">Activo</span>' : '<span class="text-gray-400">Inactivo</span>' !!}</dd></div>
                <div>
                    <dt class="text-gray-400">Stock actual</dt>
                    <dd class="font-medium text-lg {{ $producto->stock_bajo ? 'text-red-500' : 'text-gray-800' }}">
                        {{ $producto->stock_actual }} {{ $producto->stock_bajo ? '⚠ stock bajo' : '' }}
                    </dd>
                </div>
                <div><dt class="text-gray-400">Stock mínimo</dt><dd class="text-gray-800 font-medium">{{ $producto->stock_minimo }}</dd></div>
            </dl>
            @if ($producto->descripcion)
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <dt class="text-gray-400 text-sm mb-1">Descripción</dt>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $producto->descripcion }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Últimos movimientos --}}
    <div class="bg-white rounded-xl border border-sweetgo-pink-light mt-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-sweetgo-pink-light flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Últimos movimientos</h3>
            <a href="{{ route('stock.kardex', $producto) }}" class="text-xs text-sweetgo-turquoise hover:underline">Ver todo el kardex →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-6 py-2 font-medium">Fecha</th>
                    <th class="px-6 py-2 font-medium">Tipo</th>
                    <th class="px-6 py-2 font-medium text-center">Cantidad</th>
                    <th class="px-6 py-2 font-medium text-center">Stock resultante</th>
                    <th class="px-6 py-2 font-medium">Motivo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($movimientos as $m)
                    <tr>
                        <td class="px-6 py-2 text-gray-500">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-2"><x-mov-badge :tipo="$m->tipo" /></td>
                        <td class="px-6 py-2 text-center font-medium">{{ $m->cantidad }}</td>
                        <td class="px-6 py-2 text-center">{{ $m->stock_nuevo }}</td>
                        <td class="px-6 py-2 text-gray-500">{{ $m->motivo ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400">Sin movimientos aún.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movimientos->links() }}</div>
@endsection
