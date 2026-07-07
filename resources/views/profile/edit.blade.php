@extends('layouts.admin')

@section('title', 'Mi perfil')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Mi perfil</h2>
        <p class="text-sm text-gray-500">Actualiza tus datos de acceso y contraseña.</p>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="mb-4 rounded-lg bg-sweetgo-turquoise-light border border-sweetgo-turquoise text-teal-800 px-4 py-3 text-sm max-w-3xl">
            Datos actualizados correctamente.
        </div>
    @endif
    @if (session('status') === 'password-updated')
        <div class="mb-4 rounded-lg bg-sweetgo-turquoise-light border border-sweetgo-turquoise text-teal-800 px-4 py-3 text-sm max-w-3xl">
            Contraseña actualizada correctamente.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-5xl">
        {{-- Datos personales --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <h3 class="font-semibold text-gray-700 mb-4">Datos personales</h3>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Nombre</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    @error('name', 'updateProfileInformation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Correo</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    @error('email', 'updateProfileInformation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="pt-2">
                    <p class="text-xs text-gray-400">
                        Rol: <span class="font-medium text-sweetgo-pink">{{ ucfirst(auth()->user()->roles->first()?->name ?? 'Sin rol') }}</span>
                    </p>
                </div>
                <div class="flex justify-end">
                    <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Guardar datos</button>
                </div>
            </form>
        </div>

        {{-- Contraseña --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <h3 class="font-semibold text-gray-700 mb-4">Cambiar contraseña</h3>
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Contraseña actual</label>
                    <input type="password" name="current_password" autocomplete="current-password"
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    @error('current_password', 'updatePassword')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Nueva contraseña</label>
                    <input type="password" name="password" autocomplete="new-password"
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    @error('password', 'updatePassword')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password"
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
                <div class="flex justify-end">
                    <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Actualizar contraseña</button>
                </div>
            </form>
        </div>
    </div>
@endsection
