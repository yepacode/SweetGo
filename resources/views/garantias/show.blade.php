@extends('layouts.admin')

@section('title', $garantia->numero)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('garantias.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a garantías</a>
            <div class="flex items-center gap-3 mt-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $garantia->numero }}</h2>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium {{ $garantia->estadoBadge() }}">{{ $garantia->estado_label }}</span>
            </div>
        </div>
        @if (auth()->user()->hasRole('admin'))
            <form method="POST" action="{{ route('garantias.destroy', $garantia) }}" onsubmit="return confirm('¿Eliminar la garantía {{ $garantia->numero }}?')">
                @csrf @method('DELETE')
                <button class="px-4 py-2 rounded-lg border border-red-200 text-red-500 text-sm hover:bg-red-50">Eliminar</button>
            </form>
        @endif
    </div>

    {{-- Stepper de estados --}}
    <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 mb-6">
        @php($orden = array_keys(\App\Models\Garantia::ESTADOS))
        @php($actualIdx = array_search($garantia->estado, $orden))
        <div class="flex items-center">
            @foreach (\App\Models\Garantia::ESTADOS as $key => $label)
                @php($idx = $loop->index)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center">
                        <div @class([
                            'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold',
                            'bg-sweetgo-pink text-white' => $idx <= $actualIdx,
                            'bg-gray-100 text-gray-400' => $idx > $actualIdx,
                        ])>{{ $idx + 1 }}</div>
                        <span class="mt-1 text-xs {{ $idx <= $actualIdx ? 'text-gray-700 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
                    </div>
                    @unless ($loop->last)
                        <div class="flex-1 h-0.5 mx-2 {{ $idx < $actualIdx ? 'bg-sweetgo-pink' : 'bg-gray-100' }}"></div>
                    @endunless
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Info + evidencias --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-400">Cliente</dt><dd class="text-gray-800 font-medium"><a href="{{ route('clientes.show', $garantia->cliente) }}" class="hover:text-sweetgo-pink">{{ $garantia->cliente?->nombre }}</a></dd></div>
                    <div><dt class="text-gray-400">Producto</dt><dd class="text-gray-800">{{ $garantia->producto_display }}</dd></div>
                    <div><dt class="text-gray-400">Recibido</dt><dd class="text-gray-800">{{ $garantia->fecha_recibido->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gray-400">Registró</dt><dd class="text-gray-800">{{ $garantia->vendedor?->name ?? '—' }}</dd></div>
                    @if ($garantia->fecha_cierre)
                        <div><dt class="text-gray-400">Cerrada</dt><dd class="text-gray-800">{{ $garantia->fecha_cierre->format('d/m/Y') }}</dd></div>
                    @endif
                </dl>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <dt class="text-gray-400 text-sm mb-1">Problema reportado</dt>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $garantia->descripcion }}</p>
                </div>
                @if ($garantia->solucion)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <dt class="text-sweetgo-turquoise text-sm mb-1 font-medium">Solución / gestión</dt>
                        <p class="text-sm text-gray-700 leading-relaxed">{{ $garantia->solucion }}</p>
                    </div>
                @endif
            </div>

            {{-- Evidencias --}}
            <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700">Evidencias ({{ $garantia->documentos->count() }})</h3>
                </div>
                @if ($garantia->documentos->count())
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-5">
                        @foreach ($garantia->documentos as $doc)
                            <a href="{{ $doc->url }}" target="_blank" class="block group">
                                @if ($doc->es_imagen)
                                    <img src="{{ $doc->url }}" alt="" class="w-full h-24 object-cover rounded-lg border border-gray-100 group-hover:opacity-90">
                                @else
                                    <div class="w-full h-24 rounded-lg bg-sweetgo-pink-light flex flex-col items-center justify-center text-sweetgo-pink">
                                        <span class="text-2xl">📄</span>
                                        <span class="text-[10px] mt-1 px-1 truncate max-w-full">{{ $doc->nombre_original }}</span>
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 mb-4">Sin evidencias adjuntas.</p>
                @endif

                <form method="POST" action="{{ route('garantias.evidencias', $garantia) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="file" name="evidencias[]" multiple accept="image/*,.pdf" required
                           class="text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-sweetgo-turquoise file:text-white file:text-sm hover:file:opacity-90">
                    <button class="px-4 py-1.5 rounded-lg bg-sweetgo-turquoise text-white text-sm hover:opacity-90">Adjuntar</button>
                </form>
            </div>
        </div>

        {{-- Cambio de estado --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 h-fit">
            <h3 class="font-semibold text-gray-700 mb-4">Actualizar estado</h3>
            <form method="POST" action="{{ route('garantias.estado', $garantia) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Estado</label>
                    <select name="estado" class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        @foreach (\App\Models\Garantia::ESTADOS as $key => $label)
                            <option value="{{ $key }}" @selected($garantia->estado === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Solución / notas de gestión</label>
                    <textarea name="solucion" rows="4" placeholder="Describe la gestión o solución…"
                              class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">{{ $garantia->solucion }}</textarea>
                </div>
                <button class="w-full px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Guardar estado</button>
            </form>
        </div>
    </div>
@endsection
