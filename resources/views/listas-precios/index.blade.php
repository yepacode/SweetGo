@extends('layouts.admin')

@section('title', 'Listas de precios')

@section('content')
    <div class="mb-6">
        <a href="{{ route('productos.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a productos</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Listas de precios</h2>
        <p class="text-sm text-gray-500">Define precios distintos por tipo de cliente. La lista <strong>pública</strong> es la que ve el catálogo de WhatsApp.</p>
    </div>

    {{-- Gestión de listas --}}
    <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex-1 min-w-[280px]">
                <h3 class="font-semibold text-gray-700 mb-3">Listas</h3>
                <div class="space-y-2">
                    @foreach ($listas as $lista)
                        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-100 px-3 py-2">
                            <form method="POST" action="{{ route('listas-precios.update', $lista) }}"
                                  class="flex flex-wrap items-center gap-3">
                                @csrf @method('PATCH')
                                <input type="text" name="nombre" value="{{ $lista->nombre }}"
                                       class="rounded-lg border-gray-200 text-sm py-1.5 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                                    <input type="checkbox" name="es_publica" value="1" @checked($lista->es_publica)
                                           class="rounded border-gray-300 text-sweetgo-turquoise focus:ring-sweetgo-turquoise"> Pública (catálogo)
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-xs text-gray-600">
                                    <input type="checkbox" name="es_predeterminada" value="1" @checked($lista->es_predeterminada)
                                           class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink"> Predeterminada
                                </label>
                                <button class="text-xs text-sweetgo-pink font-medium hover:underline">Guardar</button>
                            </form>
                            @unless ($lista->es_publica || $lista->es_predeterminada)
                                <form method="POST" action="{{ route('listas-precios.destroy', $lista) }}"
                                      onsubmit="return confirm('¿Eliminar la lista «{{ $lista->nombre }}»?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600">Eliminar</button>
                                </form>
                            @endunless
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('listas-precios.store') }}" class="flex items-end gap-2">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nueva lista</label>
                    <input type="text" name="nombre" placeholder="Ej. Distribuidor" required
                           class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
                <button class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Crear</button>
            </form>
        </div>
    </div>

    {{-- Matriz de precios --}}
    <form method="POST" action="{{ route('listas-precios.guardar') }}">
        @csrf
        <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
            <div class="px-6 py-4 border-b border-sweetgo-pink-light flex items-center justify-between">
                <h3 class="font-semibold text-gray-700">Precios por producto</h3>
                <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Guardar precios</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left sticky top-0">
                        <tr>
                            <th class="px-4 py-3 font-medium">Producto</th>
                            <th class="px-4 py-3 font-medium">Ref.</th>
                            @foreach ($listas as $lista)
                                <th class="px-4 py-3 font-medium text-right whitespace-nowrap">
                                    {{ $lista->nombre }}
                                    @if ($lista->es_publica)<span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full bg-sweetgo-turquoise-light text-teal-700">pública</span>@endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($productos as $producto)
                            <tr class="hover:bg-sweetgo-pink-light/20">
                                <td class="px-4 py-2 font-medium text-gray-800 whitespace-nowrap">{{ $producto->nombre }}</td>
                                <td class="px-4 py-2 text-gray-400">{{ $producto->referencia }}</td>
                                @foreach ($listas as $lista)
                                    @php($valor = $producto->preciosProducto->firstWhere('lista_precio_id', $lista->id)?->precio ?? $producto->precio)
                                    <td class="px-4 py-2">
                                        <div class="flex items-center justify-end gap-1">
                                            <span class="text-gray-400 text-xs">$</span>
                                            <input type="number" min="0" step="1"
                                                   name="precios[{{ $producto->id }}][{{ $lista->id }}]"
                                                   value="{{ (int) $valor }}"
                                                   class="w-24 rounded-lg border-gray-200 text-sm text-right py-1 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-sweetgo-pink-light text-right">
                <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Guardar precios</button>
            </div>
        </div>
    </form>
@endsection
