@extends('layouts.admin')

@section('title', $cliente->nombre)

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('clientes.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a clientes</a>
            <h2 class="text-xl font-semibold text-gray-800 mt-1">{{ $cliente->nombre }}</h2>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('garantias.create', ['cliente' => $cliente->id]) }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">Registrar garantía</a>
            <a href="{{ route('cotizaciones.create', ['cliente' => $cliente->id]) }}" class="px-4 py-2 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-sm hover:bg-sweetgo-turquoise-light">Nueva cotización</a>
            <a href="{{ route('clientes.edit', $cliente) }}" class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm hover:opacity-90">Editar</a>
        </div>
    </div>

    @php($garantiasAbiertas = $cliente->garantias->whereIn('estado', \App\Models\Garantia::ABIERTOS)->count())
    @if ($garantiasAbiertas > 0)
        <div class="mb-6 flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            <span class="text-lg">⚠</span>
            Este cliente tiene <strong>{{ $garantiasAbiertas }}</strong> garantía(s) abierta(s) en gestión.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Ficha --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-400">Documento</dt><dd class="text-gray-800">{{ $cliente->tipo_documento }} {{ $cliente->documento ?? '—' }}</dd></div>
                <div>
                    <dt class="text-gray-400">Teléfono{{ $cliente->telefonos->count() > 1 ? 's' : '' }}</dt>
                    <dd class="text-gray-800">
                        @forelse ($cliente->telefonos as $t)
                            <div>{{ $t->numero }}@if ($t->etiqueta) <span class="text-xs text-gray-400">· {{ $t->etiqueta }}</span>@endif</div>
                        @empty
                            {{ $cliente->telefono ?? '—' }}
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400">Correo{{ $cliente->emails->count() > 1 ? 's' : '' }}</dt>
                    <dd class="text-gray-800">
                        @forelse ($cliente->emails as $e)
                            <div>{{ $e->email }}@if ($e->etiqueta) <span class="text-xs text-gray-400">· {{ $e->etiqueta }}</span>@endif</div>
                        @empty
                            {{ $cliente->email ?? '—' }}
                        @endforelse
                    </dd>
                </div>
                <div><dt class="text-gray-400">Dirección</dt><dd class="text-gray-800">{{ $cliente->direccion ?? '—' }}{{ $cliente->ciudad ? ', '.$cliente->ciudad : '' }}</dd></div>
                <div><dt class="text-gray-400">Lista de precios</dt><dd class="text-gray-800 font-medium">{{ $cliente->listaPrecio?->nombre ?? \App\Models\ListaPrecio::predeterminada()?->nombre ?? '—' }}</dd></div>
                <div><dt class="text-gray-400">Estado</dt><dd>{!! $cliente->activo ? '<span class="text-sweetgo-turquoise font-medium">Activo</span>' : '<span class="text-gray-400">Inactivo</span>' !!}</dd></div>
                @if ($cliente->notas)
                    <div><dt class="text-gray-400">Notas</dt><dd class="text-gray-600">{{ $cliente->notas }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Historial de cotizaciones --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
            <div class="px-6 py-4 border-b border-sweetgo-pink-light">
                <h3 class="font-semibold text-gray-700">Historial de cotizaciones</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr>
                        <th class="px-6 py-2 font-medium">Número</th>
                        <th class="px-6 py-2 font-medium">Fecha</th>
                        <th class="px-6 py-2 font-medium">Estado</th>
                        <th class="px-6 py-2 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cliente->cotizaciones as $cot)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-6 py-2"><a href="{{ route('cotizaciones.show', $cot) }}" class="font-medium text-sweetgo-pink hover:underline">{{ $cot->numero }}</a></td>
                            <td class="px-6 py-2 text-gray-500">{{ $cot->fecha->format('d/m/Y') }}</td>
                            <td class="px-6 py-2"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $cot->estadoBadge() }}">{{ ucfirst($cot->estado) }}</span></td>
                            <td class="px-6 py-2 text-right font-medium">${{ number_format($cot->total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Sin cotizaciones. <a href="{{ route('cotizaciones.create', ['cliente' => $cliente->id]) }}" class="text-sweetgo-pink hover:underline">Crear la primera</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sucursales --}}
    @if ($cliente->sucursales->count())
        <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden mt-6">
            <div class="px-6 py-4 border-b border-sweetgo-pink-light">
                <h3 class="font-semibold text-gray-700">Sucursales ({{ $cliente->sucursales->count() }})</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($cliente->sucursales as $s)
                    <div class="border border-gray-100 rounded-lg p-4">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h4 class="font-medium text-gray-800 text-sm">{{ $s->nombre }}</h4>
                            @if ($s->es_principal)
                                <span class="text-[10px] uppercase bg-sweetgo-pink-light text-sweetgo-pink px-1.5 py-0.5 rounded whitespace-nowrap">Principal</span>
                            @endif
                        </div>
                        <dl class="text-xs text-gray-600 space-y-1">
                            @if ($s->direccion || $s->ciudad)
                                <div><span class="text-gray-400">Dirección:</span> {{ $s->direccion }}{{ $s->ciudad ? ', '.$s->ciudad : '' }}</div>
                            @endif
                            @if ($s->telefono)
                                <div><span class="text-gray-400">Teléfono:</span> {{ $s->telefono }}</div>
                            @endif
                            @if ($s->contacto)
                                <div><span class="text-gray-400">Contacto:</span> {{ $s->contacto }}</div>
                            @endif
                            @if ($s->notas)
                                <div class="text-gray-500 italic">{{ $s->notas }}</div>
                            @endif
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Garantías del cliente --}}
    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden mt-6">
        <div class="px-6 py-4 border-b border-sweetgo-pink-light flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Garantías</h3>
            <a href="{{ route('garantias.create', ['cliente' => $cliente->id]) }}" class="text-xs text-sweetgo-turquoise hover:underline">+ Registrar</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-6 py-2 font-medium">Número</th>
                    <th class="px-6 py-2 font-medium">Producto</th>
                    <th class="px-6 py-2 font-medium">Recibido</th>
                    <th class="px-6 py-2 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($cliente->garantias as $g)
                    <tr class="hover:bg-sweetgo-pink-light/30">
                        <td class="px-6 py-2"><a href="{{ route('garantias.show', $g) }}" class="font-medium text-sweetgo-pink hover:underline">{{ $g->numero }}</a></td>
                        <td class="px-6 py-2 text-gray-600">{{ $g->producto_display }}</td>
                        <td class="px-6 py-2 text-gray-500">{{ $g->fecha_recibido->format('d/m/Y') }}</td>
                        <td class="px-6 py-2"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $g->estadoBadge() }}">{{ $g->estado_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Sin garantías registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
