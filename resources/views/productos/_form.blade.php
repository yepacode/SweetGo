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
                <label class="block text-sm font-medium text-gray-600 mb-1">Precio base (COP) <span class="text-sweetgo-pink">*</span></label>
                <input type="number" name="precio" step="1" min="0" value="{{ old('precio', $p?->precio ? (int) $p->precio : '') }}" required
                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                <p class="mt-1 text-[11px] text-gray-400">Se usa como fallback si no defines precios por lista.</p>
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
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Stock máximo</label>
                <input type="number" name="stock_maximo" step="1" min="0" value="{{ old('stock_maximo', $p?->stock_maximo) }}"
                       placeholder="Sin límite"
                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                <p class="mt-1 text-[11px] text-gray-400">Cantidad máxima permitida por cotización.</p>
            </div>
        </div>

        {{-- Precios por lista --}}
        @if (($listas ?? collect())->count())
            <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Precios por lista</h3>
                        <p class="text-[11px] text-gray-400">Deja vacío para usar el precio base. La lista pública también actualiza el precio del catálogo.</p>
                    </div>
                    <a href="{{ route('listas-precios.index') }}" class="text-[11px] text-sweetgo-turquoise hover:underline whitespace-nowrap">Gestionar listas →</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($listas as $lista)
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1 flex items-center gap-1">
                                {{ $lista->nombre }}
                                @if ($lista->es_publica)
                                    <span class="text-[10px] uppercase bg-sweetgo-turquoise-light text-sweetgo-turquoise px-1.5 py-0.5 rounded">Pública</span>
                                @endif
                                @if ($lista->es_predeterminada)
                                    <span class="text-[10px] uppercase bg-sweetgo-pink-light text-sweetgo-pink px-1.5 py-0.5 rounded">Predet.</span>
                                @endif
                            </label>
                            <input type="number" name="precios_lista[{{ $lista->id }}]" step="1" min="0"
                                   value="{{ old('precios_lista.'.$lista->id, isset($preciosActuales[$lista->id]) ? (int) $preciosActuales[$lista->id] : '') }}"
                                   placeholder="{{ $p?->precio ? (int) $p->precio : 'Precio base' }}"
                                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Variantes del producto (color, talla, presentación…) --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6"
             x-data="{ items: {{ Illuminate\Support\Js::from(array_values(old('variantes', $variantesIniciales ?? []))) }}, listas: {{ Illuminate\Support\Js::from(($listas ?? collect())->map(fn ($l) => ['id' => $l->id, 'nombre' => $l->nombre])->values()) }} }">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">Variantes</h3>
                    <p class="text-[11px] text-gray-400">
                        Añade variantes (color, talla, presentación…) con referencia y stock propios.
                        <span x-show="items.length > 0" class="text-sweetgo-turquoise">Si hay variantes, el stock del producto se calcula sumando las variantes.</span>
                    </p>
                </div>
                <button type="button"
                        @click="items.push({id:null, nombre:'', referencia:'', stock_actual:0, stock_minimo:0, stock_maximo:'', activo:'1', precios: Object.fromEntries(listas.map(l => [l.id, '']))})"
                        class="text-[11px] text-sweetgo-turquoise hover:underline whitespace-nowrap">+ Agregar variante</button>
            </div>

            <template x-if="items.length === 0">
                <p class="text-xs text-gray-400 italic">Sin variantes. El producto se manejará con un solo stock y sus precios por lista.</p>
            </template>

            <div class="space-y-3">
                <template x-for="(v, i) in items" :key="i">
                    <div class="border border-gray-100 rounded-lg p-3 relative">
                        <button type="button" @click="items.splice(i, 1)"
                                class="absolute top-2 right-2 text-gray-300 hover:text-red-500 text-lg leading-none [&_*]:pointer-events-none">×</button>

                        <input type="hidden" :name="`variantes[${i}][id]`" :value="v.id ?? ''">
                        <input type="hidden" :name="`variantes[${i}][activo]`" :value="v.activo">

                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Nombre <span class="text-sweetgo-pink">*</span></label>
                                <input type="text" :name="`variantes[${i}][nombre]`" x-model="v.nombre" required
                                       placeholder="Rosa, Talla M, 500 ml…"
                                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Referencia <span class="text-sweetgo-pink">*</span></label>
                                <input type="text" :name="`variantes[${i}][referencia]`" x-model="v.referencia" required
                                       placeholder="Única global"
                                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Estado</label>
                                <select :name="`variantes[${i}][activo]__ignored`"
                                        @change="v.activo = $event.target.value"
                                        class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                                    <option value="1" :selected="v.activo === '1'">Activa</option>
                                    <option value="0" :selected="v.activo === '0'">Inactiva</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mt-2">
                            <div>
                                <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Stock</label>
                                <input type="number" :name="`variantes[${i}][stock_actual]`" x-model.number="v.stock_actual"
                                       min="0" step="1" :disabled="!!v.id"
                                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm disabled:bg-gray-50"
                                       :title="v.id ? 'Se ajusta desde Inventario' : ''">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Stock mín.</label>
                                <input type="number" :name="`variantes[${i}][stock_minimo]`" x-model.number="v.stock_minimo" min="0" step="1"
                                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Stock máx.</label>
                                <input type="number" :name="`variantes[${i}][stock_maximo]`" x-model="v.stock_maximo" min="0" step="1" placeholder="Sin límite"
                                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-[10px] uppercase tracking-wide text-gray-500 mb-2 font-medium">Precios por lista <span class="text-gray-400 normal-case">— vacío = usa el precio del producto padre</span></p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <template x-for="l in listas" :key="l.id">
                                    <div>
                                        <label class="block text-[10px] text-gray-500 mb-1" x-text="l.nombre"></label>
                                        <input type="number" :name="`variantes[${i}][precios][${l.id}]`" :value="v.precios?.[l.id] ?? ''"
                                               @input="v.precios = {...(v.precios||{}), [l.id]: $event.target.value}"
                                               min="0" step="1" placeholder="Padre"
                                               class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
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
