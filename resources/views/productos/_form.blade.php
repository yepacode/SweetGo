@php($p = $producto ?? null)

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Columna principal --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Nombre <span class="text-sweetgo-pink">*</span></label>
                <input type="text" name="nombre" value="{{ old('nombre', $p?->nombre) }}" required
                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Referencia</label>
                    <input type="text" name="referencia" value="{{ old('referencia', $p?->referencia) }}"
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Categoría</label>
                    <select name="categoria_id" class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <option value="">Sin categoría</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" @selected(old('categoria_id', $p?->categoria_id) == $cat->id)>{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3"
                          class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">{{ old('descripcion', $p?->descripcion) }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Precio público (COP) <span class="text-sweetgo-pink">*</span></label>
                <input type="number" name="precio" step="1" min="0" value="{{ old('precio', $p?->precio ? (int) $p->precio : '') }}" required
                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                <p class="mt-1 text-[11px] text-gray-400">Precios mayorista/otros en <a href="{{ route('listas-precios.index') }}" class="text-sweetgo-turquoise hover:underline">Listas de precios</a>.</p>
            </div>
            @unless ($p)
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Stock inicial</label>
                    <input type="number" name="stock_actual" step="1" min="0" value="{{ old('stock_actual', 0) }}"
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
            @endunless
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Stock mínimo</label>
                <input type="number" name="stock_minimo" step="1" min="0" value="{{ old('stock_minimo', $p?->stock_minimo ?? 5) }}"
                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            </div>
        </div>
    </div>

    {{-- Columna lateral --}}
    <div class="space-y-5">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <label class="block text-sm font-medium text-gray-600 mb-2">Imagen</label>
            @if ($p?->imagen)
                <img src="{{ Storage::url($p->imagen) }}" alt="" class="w-full h-40 object-cover rounded-lg mb-3 border border-gray-100">
            @else
                <div class="w-full h-40 rounded-lg bg-sweetgo-pink-light flex items-center justify-center mb-3 text-4xl text-sweetgo-pink">✦</div>
            @endif
            <x-file-input name="imagen" accept="image/*" label="Elegir imagen" hint="JPG/PNG, máx. 4 MB." />
            {{-- El input nativo queda oculto dentro del componente; el nombre real se muestra sin truncar --}}
        </div>

        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $p?->activo ?? true))
                       class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink">
                Producto activo (visible en catálogo)
            </label>
        </div>
    </div>
</div>

<div class="flex items-center justify-end gap-3 mt-6">
    <a href="{{ route('productos.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</a>
    <button class="px-6 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90">
        {{ $p ? 'Guardar cambios' : 'Crear producto' }}
    </button>
</div>
