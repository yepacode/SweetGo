@php($u = $usuario ?? null)

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 space-y-4 max-w-2xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Nombre <span class="text-sweetgo-pink">*</span></label>
            <input type="text" name="name" value="{{ old('name', $u?->name) }}" required
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Correo <span class="text-sweetgo-pink">*</span></label>
            <input type="email" name="email" value="{{ old('email', $u?->email) }}" required
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-600 mb-1">Rol <span class="text-sweetgo-pink">*</span></label>
        <select name="rol" class="w-full sm:w-1/2 rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            @foreach ($roles as $rol)
                <option value="{{ $rol }}" @selected(old('rol', $u?->roles->first()?->name ?? 'vendedor') === $rol)>{{ ucfirst($rol) }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">
                Contraseña @unless ($u)<span class="text-sweetgo-pink">*</span>@endunless
            </label>
            <input type="password" name="password" autocomplete="new-password" {{ $u ? '' : 'required' }}
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            @if ($u)<p class="mt-1 text-xs text-gray-400">Déjala en blanco para no cambiarla.</p>@endif
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" autocomplete="new-password"
                   class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
    </div>
</div>

<div class="flex items-center justify-end gap-3 mt-6 max-w-2xl">
    <a href="{{ route('usuarios.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</a>
    <button class="px-6 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90">
        {{ $u ? 'Guardar cambios' : 'Crear usuario' }}
    </button>
</div>
