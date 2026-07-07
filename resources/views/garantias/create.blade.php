@extends('layouts.admin')

@section('title', 'Registrar garantía')

@section('content')
    <div class="mb-6">
        <a href="{{ route('garantias.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a garantías</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Registrar garantía</h2>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm max-w-3xl">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('garantias.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Cliente <span class="text-sweetgo-pink">*</span></label>
                    <select name="cliente_id" required class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <option value="">Selecciona un cliente…</option>
                        @foreach ($clientes as $cl)
                            <option value="{{ $cl->id }}" @selected(old('cliente_id', request('cliente')) == $cl->id)>{{ $cl->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Fecha de recepción <span class="text-sweetgo-pink">*</span></label>
                    <input type="date" name="fecha_recibido" value="{{ old('fecha_recibido', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Producto (del catálogo)</label>
                    <select name="producto_id" class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <option value="">— Ninguno / escribir abajo —</option>
                        @foreach ($productos as $p)
                            <option value="{{ $p->id }}" @selected(old('producto_id') == $p->id)>{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Producto (texto libre)</label>
                    <input type="text" name="producto_nombre" value="{{ old('producto_nombre') }}" placeholder="Si no está en el catálogo"
                           class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Descripción del problema <span class="text-sweetgo-pink">*</span></label>
                <textarea name="descripcion" rows="4" required
                          class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink">{{ old('descripcion') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Evidencias (fotos / documentos)</label>
                <x-file-input name="evidencias" accept="image/*,.pdf" :multiple="true"
                              label="Elegir archivos"
                              hint="Puedes seleccionar varios (imagen o PDF, máx. 8 MB c/u)." />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-6">
            <a href="{{ route('garantias.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cancelar</a>
            <button class="px-6 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90">Registrar garantía</button>
        </div>
    </form>
@endsection
