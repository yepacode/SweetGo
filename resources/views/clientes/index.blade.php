@extends('layouts.admin')

@section('title', 'Clientes')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Clientes</h2>
            <p class="text-sm text-gray-500">{{ $clientes->total() }} clientes registrados</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('clientes.plantilla') }}"
                   class="px-3 py-2 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-sm hover:bg-sweetgo-turquoise-light">
                    ↓ Plantilla Excel
                </a>
                <button type="button" onclick="document.getElementById('mdImportClientes').classList.remove('hidden')"
                        class="px-3 py-2 rounded-lg border border-sweetgo-pink text-sweetgo-pink text-sm hover:bg-sweetgo-pink-light">
                    ↥ Importar Excel
                </button>
            @endif
            <a href="{{ route('clientes.create') }}"
               class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90 transition">
                + Nuevo cliente
            </a>
        </div>
    </div>

    {{-- Errores globales del import (mimes, tamaño, etc.). El modal puede haberse cerrado, así que el mensaje va afuera. --}}
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if (auth()->user()->hasRole('admin'))
        <div id="mdImportClientes" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl border border-sweetgo-pink-light shadow-lg w-full max-w-md p-6 relative">
                <button type="button" onclick="document.getElementById('mdImportClientes').classList.add('hidden')"
                        class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Importar clientes desde Excel</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Descarga la <a href="{{ route('clientes.plantilla') }}" class="text-sweetgo-pink hover:underline">plantilla</a>,
                    complétala y súbela aquí. Se asigna la lista de precios indicada en cada fila.
                </p>
                <form method="POST" action="{{ route('clientes.importar') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="archivo" accept=".xlsx,.xls,.csv,.txt" required
                           class="w-full text-sm text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded file:border-0 file:text-sm file:bg-sweetgo-pink-light file:text-sweetgo-pink">
                    <p class="text-xs text-gray-400">Formatos: XLSX, XLS, CSV · máx 8MB</p>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('mdImportClientes').classList.add('hidden')"
                                class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</button>
                        <button class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">
                            Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <form method="GET" class="mb-5">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar nombre, documento, teléfono o correo…"
               class="w-full sm:w-96 rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
    </form>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Documento</th>
                        <th class="px-4 py-3 font-medium">Contacto</th>
                        <th class="px-4 py-3 font-medium">Ciudad</th>
                        @if (auth()->user()->hasRole('admin'))
                            <th class="px-4 py-3 font-medium">Vendedor</th>
                        @endif
                        <th class="px-4 py-3 font-medium text-center">Cotizaciones</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($clientes as $c)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('clientes.show', $c) }}" class="font-medium text-gray-800 hover:text-sweetgo-pink">{{ $c->nombre }}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $c->tipo_documento }} {{ $c->documento ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $c->telefono ?? '—' }}
                                @if ($c->email)<br><span class="text-xs">{{ $c->email }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $c->ciudad ?? '—' }}</td>
                            @if (auth()->user()->hasRole('admin'))
                                <td class="px-4 py-3 text-gray-500">{{ $c->vendedor?->name ?? '—' }}</td>
                            @endif
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-sweetgo-turquoise-light text-teal-700 text-xs">{{ $c->cotizaciones_count }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-3 text-xs">
                                    <a href="{{ route('clientes.show', $c) }}" class="text-gray-500 hover:underline">Ver</a>
                                    <a href="{{ route('clientes.edit', $c) }}" class="text-sweetgo-turquoise hover:underline">Editar</a>
                                    @if (auth()->user()->hasRole('admin'))
                                        <form method="POST" action="{{ route('clientes.destroy', $c) }}"
                                              onsubmit="return confirm('¿Eliminar «{{ $c->nombre }}»?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-400 hover:text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->hasRole('admin') ? 7 : 6 }}" class="px-4 py-10 text-center text-gray-400">No hay clientes aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $clientes->links() }}</div>
@endsection
