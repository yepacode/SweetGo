@extends('layouts.admin')

@section('title', 'Categorías')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('productos.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a productos</a>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">Categorías</h2>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Nueva categoría --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 h-fit">
            <h3 class="font-semibold text-gray-700 mb-3">Nueva categoría</h3>
            <form method="POST" action="{{ route('categorias.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="nombre" placeholder="Nombre de la categoría" required
                       class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                @error('nombre')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                <button class="w-full px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Agregar</button>
            </form>
        </div>

        {{-- Listado --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium text-center">Productos</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($categorias as $cat)
                        <tr x-data="{ edit: false }" class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3">
                                <span x-show="!edit" class="font-medium text-gray-800">{{ $cat->nombre }}</span>
                                <form x-show="edit" x-cloak method="POST" action="{{ route('categorias.update', $cat) }}" class="flex gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="nombre" value="{{ $cat->nombre }}"
                                           class="rounded-lg border-gray-200 text-sm py-1 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                    <input type="hidden" name="activo" value="1">
                                    <button class="text-xs text-sweetgo-pink font-medium">Guardar</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-sweetgo-turquoise-light text-teal-700 text-xs">{{ $cat->productos_count }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3 text-xs">
                                    <button @click="edit = !edit" class="text-sweetgo-turquoise hover:underline">Editar</button>
                                    @if (auth()->user()->hasRole('admin'))
                                        <form method="POST" action="{{ route('categorias.destroy', $cat) }}"
                                              onsubmit="return confirm('¿Eliminar categoría «{{ $cat->nombre }}»?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-400 hover:text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Sin categorías.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
