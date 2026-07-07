@php($c = $cliente ?? null)

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 space-y-4 max-w-3xl">
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

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Teléfono</label>
            <input type="text" name="telefono" value="{{ old('telefono', $c?->telefono) }}"
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Correo</label>
            <input type="email" name="email" value="{{ old('email', $c?->email) }}"
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Dirección</label>
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

<div class="flex items-center justify-end gap-3 mt-6 max-w-3xl">
    <a href="{{ route('clientes.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</a>
    <button class="px-6 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90">
        {{ $c ? 'Guardar cambios' : 'Crear cliente' }}
    </button>
</div>
