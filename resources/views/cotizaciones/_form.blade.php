@php($cot = $cotizacion ?? null)

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div x-data="cotizacionForm({
        productos: {{ Illuminate\Support\Js::from($productos) }},
        itemsIniciales: {{ Illuminate\Support\Js::from($itemsIniciales) }},
        descuentoInicial: {{ old('descuento', $cot?->descuento ?? 0) }},
        clientesLista: {{ Illuminate\Support\Js::from($clientesLista) }},
        listasNombres: {{ Illuminate\Support\Js::from($listasNombres) }},
        predeterminadaId: {{ $predeterminadaId ?? 'null' }},
        clienteInicial: {{ old('cliente_id', $cot?->cliente_id ?? request('cliente') ?? 'null') ?: 'null' }}
     })"
     class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Columna principal: items --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Cliente <span class="text-sweetgo-pink">*</span></label>
                    <select name="cliente_id" required x-model.number="clienteId" @change="onCliente()"
                            class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <option value="">Selecciona un cliente…</option>
                        @foreach ($clientes as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->nombre }}@if ($cl->listaPrecio) · {{ $cl->listaPrecio->nombre }}@endif</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="listaNombre" x-text="'Lista aplicada: ' + listaNombre"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Fecha <span class="text-sweetgo-pink">*</span></label>
                    <input type="date" name="fecha" value="{{ old('fecha', $cot?->fecha?->format('Y-m-d') ?? date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
            <div class="px-6 py-4 border-b border-sweetgo-pink-light flex items-center justify-between">
                <h3 class="font-semibold text-gray-700">Productos</h3>
                <button type="button" @click="agregar()" class="px-3 py-1.5 rounded-lg bg-sweetgo-turquoise text-white text-xs font-medium hover:opacity-90">+ Agregar producto</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Producto</th>
                            <th class="px-4 py-2 font-medium w-24 text-center">Cantidad</th>
                            <th class="px-4 py-2 font-medium w-32 text-right">Precio</th>
                            <th class="px-4 py-2 font-medium w-32 text-right">Subtotal</th>
                            <th class="px-2 py-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in items" :key="idx">
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-2">
                                    <select :name="`items[${idx}][producto_id]`" x-model.number="item.producto_id" @change="onProducto(idx)" required
                                            class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                        <option value="">Selecciona…</option>
                                        @foreach ($productos as $p)
                                            <option value="{{ $p['id'] }}">{{ $p['nombre'] }}{{ $p['referencia'] ? ' ('.$p['referencia'].')' : '' }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1" x-show="item.producto_id" x-text="'Stock disponible: ' + stockDe(item.producto_id)"></p>
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" min="1" :name="`items[${idx}][cantidad]`" x-model.number="item.cantidad"
                                           class="w-full rounded-lg border-gray-200 text-sm text-center focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="number" min="0" :name="`items[${idx}][precio_unitario]`" x-model.number="item.precio_unitario"
                                           class="w-full rounded-lg border-gray-200 text-sm text-right focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                </td>
                                <td class="px-4 py-2 text-right font-medium text-gray-700" x-text="money(item.cantidad * item.precio_unitario)"></td>
                                <td class="px-2 py-2 text-center">
                                    <button type="button" @click="quitar(idx)" class="text-red-400 hover:text-red-600">&times;</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">Sin productos. Usa «+ Agregar producto».</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <label class="block text-sm font-medium text-gray-600 mb-1">Notas</label>
            <textarea name="notas" rows="2" class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">{{ old('notas', $cot?->notas) }}</textarea>
        </div>
    </div>

    {{-- Columna lateral: totales --}}
    <div class="space-y-5">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 space-y-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Subtotal</span>
                <span class="font-medium text-gray-800" x-text="money(subtotal())"></span>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Descuento (COP)</label>
                <input type="number" min="0" name="descuento" x-model.number="descuento"
                       class="w-full rounded-lg border-gray-200 text-sm text-right focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Válida hasta</label>
                <input type="date" name="validez" value="{{ old('validez', $cot?->validez?->format('Y-m-d')) }}"
                       class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="font-semibold text-gray-700">Total</span>
                <span class="text-xl font-bold text-sweetgo-pink" x-text="money(total())"></span>
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <button class="px-6 py-2.5 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90">
                {{ $cot ? 'Guardar cambios' : 'Crear cotización' }}
            </button>
            <a href="{{ $cot ? route('cotizaciones.show', $cot) : route('cotizaciones.index') }}"
               class="px-6 py-2.5 rounded-lg border border-gray-200 text-gray-500 text-sm text-center hover:bg-gray-50">Cancelar</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cotizacionForm({ productos, itemsIniciales, descuentoInicial, clientesLista, listasNombres, predeterminadaId, clienteInicial }) {
    return {
        productos, clientesLista, listasNombres, predeterminadaId,
        clienteId: clienteInicial || '',
        items: itemsIniciales.map(i => ({
            producto_id: i.producto_id ? Number(i.producto_id) : '',
            cantidad: i.cantidad ? Number(i.cantidad) : 1,
            precio_unitario: i.precio_unitario ? Number(i.precio_unitario) : 0,
        })),
        descuento: Number(descuentoInicial) || 0,
        get listaActiva() {
            const l = this.clientesLista[this.clienteId];
            return l || this.predeterminadaId || null;
        },
        get listaNombre() {
            return this.listaActiva ? (this.listasNombres[this.listaActiva] || '') : '';
        },
        precioDe(id) {
            const p = this.prod(id);
            if (!p) return 0;
            const la = this.listaActiva;
            if (la && p.precios && p.precios[la] != null) return Number(p.precios[la]);
            return Number(p.precio);
        },
        agregar() { this.items.push({ producto_id: '', cantidad: 1, precio_unitario: 0 }); },
        quitar(idx) { this.items.splice(idx, 1); },
        prod(id) { return this.productos.find(p => Number(p.id) === Number(id)); },
        stockDe(id) { const p = this.prod(id); return p ? p.stock_actual : 0; },
        onProducto(idx) {
            if (this.items[idx].producto_id) this.items[idx].precio_unitario = this.precioDe(this.items[idx].producto_id);
        },
        onCliente() {
            // Reprecia los ítems que ya tienen producto según la lista del nuevo cliente.
            this.items.forEach((it) => { if (it.producto_id) it.precio_unitario = this.precioDe(it.producto_id); });
        },
        subtotal() { return this.items.reduce((s, i) => s + (Number(i.cantidad) || 0) * (Number(i.precio_unitario) || 0), 0); },
        total() { return Math.max(0, this.subtotal() - (Number(this.descuento) || 0)); },
        money(v) { return '$' + (Math.round(v || 0)).toLocaleString('es-CO'); },
    };
}
</script>
@endpush
