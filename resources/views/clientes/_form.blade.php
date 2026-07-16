@php
    $c = $cliente ?? null;
    // Prellenar los repetidores desde old() o desde el modelo (edit) o defaults (create).
    $telsIniciales = old('telefonos');
    if (! is_array($telsIniciales) || empty($telsIniciales)) {
        $telsIniciales = $c?->telefonos?->map(fn ($t) => ['etiqueta' => $t->etiqueta, 'numero' => $t->numero])->all() ?: [];
        if (empty($telsIniciales) && $c?->telefono) {
            $telsIniciales = [['etiqueta' => 'Principal', 'numero' => $c->telefono]];
        }
    }
    if (empty($telsIniciales)) {
        $telsIniciales = [['etiqueta' => 'Principal', 'numero' => '']];
    }

    $emailsIniciales = old('emails');
    if (! is_array($emailsIniciales) || empty($emailsIniciales)) {
        $emailsIniciales = $c?->emails?->map(fn ($e) => ['etiqueta' => $e->etiqueta, 'email' => $e->email])->all() ?: [];
        if (empty($emailsIniciales) && $c?->email) {
            $emailsIniciales = [['etiqueta' => 'Principal', 'email' => $c->email]];
        }
    }
    if (empty($emailsIniciales)) {
        $emailsIniciales = [['etiqueta' => 'Principal', 'email' => '']];
    }

    $sucursalesIniciales = old('sucursales');
    if (! is_array($sucursalesIniciales)) {
        $sucursalesIniciales = $c?->sucursales?->map(fn ($s) => [
            'nombre' => $s->nombre, 'direccion' => $s->direccion, 'ciudad' => $s->ciudad,
            'telefono' => $s->telefono, 'contacto' => $s->contacto, 'notas' => $s->notas,
            'es_principal' => $s->es_principal ? '1' : '0',
        ])->all() ?: [];
    }
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 space-y-4 max-w-4xl">
    <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">Nombre / Razón social <span class="text-sweetgo-pink">*</span></label>
        <input type="text" name="nombre" value="{{ old('nombre', $c?->nombre) }}" required
               class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Tipo doc.</label>
            <select name="tipo_documento" class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                @foreach (['', 'CC', 'NIT', 'CE', 'PAS'] as $td)
                    <option value="{{ $td }}" @selected(old('tipo_documento', $c?->tipo_documento) === $td)>{{ $td ?: '—' }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-600 mb-1">Documento</label>
            <input type="text" name="documento" value="{{ old('documento', $c?->documento) }}"
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
    </div>

    {{-- Teléfonos (repetidor) --}}
    <div x-data="{ items: {{ json_encode(array_values($telsIniciales)) }} }">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-600">Teléfonos</label>
            <button type="button" @click="items.push({etiqueta:'', numero:''})"
                    class="text-xs text-sweetgo-turquoise hover:underline">+ Agregar teléfono</button>
        </div>
        <div class="space-y-2">
            <template x-for="(item, i) in items" :key="i">
                <div class="flex gap-2">
                    <input type="text" :name="`telefonos[${i}][etiqueta]`" x-model="item.etiqueta"
                           placeholder="Etiqueta (Principal, WhatsApp…)"
                           class="w-1/3 rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    <input type="text" :name="`telefonos[${i}][numero]`" x-model="item.numero"
                           placeholder="Número"
                           class="flex-1 rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    <button type="button" @click="items.splice(i,1)" x-show="items.length > 1"
                            class="px-3 rounded-lg border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 text-lg leading-none" title="Quitar">×</button>
                </div>
            </template>
        </div>
    </div>

    {{-- Correos (repetidor) --}}
    <div x-data="{ items: {{ json_encode(array_values($emailsIniciales)) }} }">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-600">Correos</label>
            <button type="button" @click="items.push({etiqueta:'', email:''})"
                    class="text-xs text-sweetgo-turquoise hover:underline">+ Agregar correo</button>
        </div>
        <div class="space-y-2">
            <template x-for="(item, i) in items" :key="i">
                <div class="flex gap-2">
                    <input type="text" :name="`emails[${i}][etiqueta]`" x-model="item.etiqueta"
                           placeholder="Etiqueta (Contacto, Facturación…)"
                           class="w-1/3 rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    <input type="email" :name="`emails[${i}][email]`" x-model="item.email"
                           placeholder="correo@ejemplo.com"
                           class="flex-1 rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    <button type="button" @click="items.splice(i,1)" x-show="items.length > 1"
                            class="px-3 rounded-lg border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 text-lg leading-none" title="Quitar">×</button>
                </div>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Dirección principal</label>
            <input type="text" name="direccion" value="{{ old('direccion', $c?->direccion) }}"
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Ciudad</label>
            <input type="text" name="ciudad" value="{{ old('ciudad', $c?->ciudad) }}"
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">Lista de precios</label>
        <select name="lista_precio_id" class="w-full sm:w-1/2 rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            @foreach ($listas as $lista)
                <option value="{{ $lista->id }}" @selected(old('lista_precio_id', $c?->lista_precio_id ?? $listaDefault) == $lista->id)>
                    {{ $lista->nombre }}@if ($lista->es_predeterminada) (predeterminada)@endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-400">Determina qué precios se aplican al cotizar para este cliente.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">Notas</label>
        <textarea name="notas" rows="2" class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">{{ old('notas', $c?->notas) }}</textarea>
    </div>

    <label class="flex items-center gap-2 text-sm text-gray-600">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" @checked(old('activo', $c?->activo ?? true))
               class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink">
        Cliente activo
    </label>
</div>

{{-- Sucursales (repetidor) --}}
<div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 mt-6 max-w-4xl"
     x-data="{ items: {{ json_encode(array_values($sucursalesIniciales)) }} }">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Sucursales</h3>
            <p class="text-xs text-gray-400">Sedes adicionales del cliente. Marca una como principal si aplica.</p>
        </div>
        <button type="button"
                @click="items.push({nombre:'', direccion:'', ciudad:'', telefono:'', contacto:'', notas:'', es_principal:'0'})"
                class="text-xs text-sweetgo-turquoise hover:underline">+ Agregar sucursal</button>
    </div>

    <template x-if="items.length === 0">
        <p class="text-xs text-gray-400 italic">Sin sucursales adicionales.</p>
    </template>

    <div class="space-y-4">
        <template x-for="(item, i) in items" :key="i">
            <div class="border border-gray-100 rounded-lg p-4 relative">
                <button type="button" @click="items.splice(i,1)"
                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-lg leading-none" title="Quitar">×</button>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Nombre <span class="text-sweetgo-pink">*</span></label>
                        <input type="text" :name="`sucursales[${i}][nombre]`" x-model="item.nombre" required
                               placeholder="Sede Norte, Salón Chapinero…"
                               class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Contacto / encargado</label>
                        <input type="text" :name="`sucursales[${i}][contacto]`" x-model="item.contacto"
                               class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Dirección</label>
                        <input type="text" :name="`sucursales[${i}][direccion]`" x-model="item.direccion"
                               class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Ciudad</label>
                            <input type="text" :name="`sucursales[${i}][ciudad]`" x-model="item.ciudad"
                                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Teléfono</label>
                            <input type="text" :name="`sucursales[${i}][telefono]`" x-model="item.telefono"
                                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Notas</label>
                        <input type="text" :name="`sucursales[${i}][notas]`" x-model="item.notas"
                               class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-gray-600 sm:col-span-2">
                        <input type="hidden" :name="`sucursales[${i}][es_principal]`" value="0">
                        <input type="checkbox" :name="`sucursales[${i}][es_principal]`" value="1"
                               :checked="item.es_principal == '1'" @change="item.es_principal = $event.target.checked ? '1' : '0'"
                               class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink">
                        Sucursal principal
                    </label>
                </div>
            </div>
        </template>
    </div>
</div>

<div class="flex items-center justify-end gap-3 mt-6 max-w-4xl">
    <a href="{{ route('clientes.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</a>
    <button class="px-6 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90">
        {{ $c ? 'Guardar cambios' : 'Crear cliente' }}
    </button>
</div>
