@extends('layouts.admin')

@section('title', 'Zonas de envío')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Zonas de envío</h2>
            <p class="text-sm text-gray-500">Define tarifas por zona: costo base + cargo por kilogramo adicional.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form crear --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 h-fit">
            <h3 class="font-semibold text-gray-700 mb-4">Nueva zona</h3>
            <form method="POST" action="{{ route('zonas-envio.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nombre <span class="text-sweetgo-pink">*</span></label>
                    <input type="text" name="nombre" required value="{{ old('nombre') }}" maxlength="120"
                           placeholder="Bogotá centro, Medellín, Nacional…"
                           class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Costo base (COP) <span class="text-sweetgo-pink">*</span></label>
                        <input type="number" name="costo_base" required value="{{ old('costo_base', 0) }}" min="0" step="1"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">$ por kg adicional <span class="text-sweetgo-pink">*</span></label>
                        <input type="number" name="costo_kg_adicional" required value="{{ old('costo_kg_adicional', 0) }}" min="0" step="1"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Peso base (kg) <span class="text-sweetgo-pink">*</span></label>
                        <input type="number" name="peso_base_kg" required value="{{ old('peso_base_kg', 1) }}" min="0.001" step="0.001"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <p class="text-[10px] text-gray-400 mt-1">Incluido en el costo base</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Peso máx (kg)</label>
                        <input type="number" name="peso_maximo_kg" value="{{ old('peso_maximo_kg') }}" min="0.001" step="0.001"
                               placeholder="Sin límite"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Notas</label>
                    <textarea name="notas" rows="2"
                              class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">{{ old('notas') }}</textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1" checked
                           class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink">
                    Activa
                </label>
                <button class="w-full mt-2 px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">
                    Crear zona
                </button>
            </form>
        </div>

        {{-- Lista de zonas --}}
        <div class="lg:col-span-2 space-y-3">
            @forelse ($zonas as $zona)
                <div x-data="{ editar: false }" class="bg-white rounded-xl border border-sweetgo-pink-light p-5"
                     :class="{{ $zona->activo ? 'true' : 'false' }} ? '' : 'opacity-60'">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-semibold text-gray-800">{{ $zona->nombre }}</h4>
                                @if ($zona->activo)
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs">Activa</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs">Inactiva</span>
                                @endif
                            </div>
                            <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Costo base</p>
                                    <p class="font-medium">${{ number_format($zona->costo_base, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">$/kg adicional</p>
                                    <p class="font-medium">${{ number_format($zona->costo_kg_adicional, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Peso base</p>
                                    <p class="font-medium">{{ rtrim(rtrim(number_format($zona->peso_base_kg, 3, '.', ''), '0'), '.') }} kg</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Peso máx</p>
                                    <p class="font-medium">{{ $zona->peso_maximo_kg ? rtrim(rtrim(number_format($zona->peso_maximo_kg, 3, '.', ''), '0'), '.') . ' kg' : 'Sin límite' }}</p>
                                </div>
                            </div>
                            @if ($zona->notas)
                                <p class="mt-2 text-xs text-gray-500 italic">{{ $zona->notas }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="editar = !editar"
                                    class="text-xs text-sweetgo-turquoise hover:underline" x-text="editar ? 'Cancelar' : 'Editar'"></button>
                            <form method="POST" action="{{ route('zonas-envio.toggle', $zona) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs text-gray-500 hover:underline">{{ $zona->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                            <form method="POST" action="{{ route('zonas-envio.destroy', $zona) }}"
                                  onsubmit="return confirm('¿Eliminar «{{ $zona->nombre }}»?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-400 hover:text-red-600">Eliminar</button>
                            </form>
                        </div>
                    </div>

                    {{-- Form edición inline --}}
                    <form x-show="editar" x-cloak method="POST" action="{{ route('zonas-envio.update', $zona) }}"
                          class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nombre</label>
                            <input type="text" name="nombre" required value="{{ $zona->nombre }}" maxlength="120"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Costo base (COP)</label>
                            <input type="number" name="costo_base" required value="{{ (int) $zona->costo_base }}" min="0" step="1"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">$ por kg adicional</label>
                            <input type="number" name="costo_kg_adicional" required value="{{ (int) $zona->costo_kg_adicional }}" min="0" step="1"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Peso base (kg)</label>
                            <input type="number" name="peso_base_kg" required value="{{ $zona->peso_base_kg }}" min="0.001" step="0.001"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Peso máx (kg)</label>
                            <input type="number" name="peso_maximo_kg" value="{{ $zona->peso_maximo_kg }}" min="0.001" step="0.001"
                                   placeholder="Sin límite"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Notas</label>
                            <input type="text" name="notas" value="{{ $zona->notas }}"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <input type="hidden" name="activo" value="{{ (int) $zona->activo }}">
                        <div class="sm:col-span-2 flex justify-end gap-2">
                            <button type="button" @click="editar = false"
                                    class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">Cancelar</button>
                            <button class="px-4 py-1.5 rounded-lg bg-sweetgo-pink text-white text-sm hover:opacity-90">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-dashed border-sweetgo-pink-light p-10 text-center text-gray-400">
                    Aún no hay zonas de envío. Crea la primera con el formulario de la izquierda.
                </div>
            @endforelse
        </div>
    </div>
@endsection
