@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Catálogo de productos</h2>
            <p class="text-sm text-gray-500">{{ $productos->total() }} productos registrados</p>
        </div>
        <div class="flex flex-wrap items-center gap-2" x-data="{ importar: false }">
            <a href="{{ route('categorias.index') }}"
               class="px-4 py-2 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-sm font-medium hover:bg-sweetgo-turquoise-light transition">
                Categorías
            </a>
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('listas-precios.index') }}"
                   class="px-4 py-2 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-sm font-medium hover:bg-sweetgo-turquoise-light transition">
                    Listas de precios
                </a>
                <button @click="importar = true"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm font-medium hover:bg-gray-50 transition">
                    Importar Excel
                </button>
            @endif
            <a href="{{ route('productos.create') }}"
               class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90 transition">
                + Nuevo producto
            </a>

            {{-- Modal importar --}}
            <div x-show="importar" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="importar = false" class="absolute inset-0 bg-black/40"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 text-left">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Importar productos</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Sube un archivo Excel/CSV con las columnas
                        <span class="font-medium">nombre, referencia, categoria, precio, stock, stock_minimo</span>.
                        Coincide por nombre: crea o actualiza.
                    </p>
                    <a href="{{ route('productos.plantilla') }}" class="inline-block mb-4 text-sm text-sweetgo-turquoise hover:underline">↓ Descargar plantilla CSV</a>
                    <form method="POST" action="{{ route('productos.importar') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <input type="file" name="archivo" accept=".xlsx,.xls,.csv" required
                               class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-sweetgo-pink file:text-white file:text-sm hover:file:opacity-90">
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="importar = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</button>
                            <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Importar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-xl border border-sweetgo-pink-light p-4 mb-5 grid grid-cols-1 sm:grid-cols-4 gap-3">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar nombre o referencia…"
               class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink" />
        <select name="categoria" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $cat)
                <option value="{{ $cat->id }}" @selected(request('categoria') == $cat->id)>{{ $cat->nombre }}</option>
            @endforeach
        </select>
        <select name="estado" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Todos los estados</option>
            <option value="activos" @selected(request('estado')==='activos')>Activos</option>
            <option value="inactivos" @selected(request('estado')==='inactivos')>Inactivos</option>
            <option value="stock_bajo" @selected(request('estado')==='stock_bajo')>Stock bajo</option>
        </select>
        <div class="flex gap-2">
            <button class="flex-1 px-4 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700">Filtrar</button>
            <a href="{{ route('productos.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 font-medium">Referencia</th>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium text-right">Precio</th>
                        <th class="px-4 py-3 font-medium text-center">Stock</th>
                        <th class="px-4 py-3 font-medium text-center">Estado</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($productos as $p)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-sweetgo-pink-light flex items-center justify-center overflow-hidden flex-shrink-0">
                                        @if ($p->imagen)
                                            <img src="{{ Storage::url($p->imagen) }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-sweetgo-pink text-lg">✦</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('productos.show', $p) }}" class="font-medium text-gray-800 hover:text-sweetgo-pink">{{ $p->nombre }}</a>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $p->referencia ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($p->categoria)
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-sweetgo-turquoise-light text-teal-700 text-xs">{{ $p->categoria->nombre }}</span>
                                @else
                                    <span class="text-gray-400 text-xs">Sin categoría</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">${{ number_format($p->precio, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'inline-block px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-red-100 text-red-600' => $p->stock_bajo,
                                    'bg-gray-100 text-gray-600' => !$p->stock_bajo,
                                ])>
                                    {{ $p->stock_actual }}
                                    @if ($p->stock_bajo) ⚠ @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($p->activo)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-teal-700">
                                        <span class="w-2 h-2 rounded-full bg-sweetgo-turquoise" aria-hidden="true"></span> Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                                        <span class="w-2 h-2 rounded-full bg-gray-300" aria-hidden="true"></span> Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2 text-xs">
                                    <a href="{{ route('productos.edit', $p) }}" class="text-sweetgo-turquoise hover:underline">Editar</a>
                                    @if (auth()->user()->hasRole('admin'))
                                        <form method="POST" action="{{ route('productos.destroy', $p) }}"
                                              onsubmit="return confirm('¿Eliminar «{{ $p->nombre }}»?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-400 hover:text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">No hay productos que coincidan con el filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $productos->links() }}</div>
@endsection
