@extends('layouts.admin')

@section('title', $cotizacion->numero)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('cotizaciones.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a cotizaciones</a>
            <div class="flex items-center gap-3 mt-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $cotizacion->numero }}</h2>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cotizacion->estadoBadge() }}">{{ ucfirst($cotizacion->estado) }}</span>
                @if ($cotizacion->esta_vencida)
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Vencida el {{ $cotizacion->validez->format('d/m/Y') }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('cotizaciones.pdf', $cotizacion) }}" target="_blank"
               class="px-4 py-2 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-sm hover:bg-sweetgo-turquoise-light">Ver PDF</a>

            <form method="POST" action="{{ route('cotizaciones.duplicar', $cotizacion) }}">
                @csrf
                <button class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">Duplicar</button>
            </form>

            @if ($cotizacion->estado !== 'aprobada')
                <a href="{{ route('cotizaciones.edit', $cotizacion) }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">Editar</a>
            @endif

            {{-- Acciones de estado --}}
            @if ($cotizacion->estado === 'borrador')
                <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}">
                    @csrf @method('PATCH')<input type="hidden" name="estado" value="enviada">
                    <button class="px-4 py-2 rounded-lg bg-sweetgo-turquoise text-white text-sm hover:opacity-90">Marcar enviada</button>
                </form>
            @endif
            @if (in_array($cotizacion->estado, ['borrador','enviada']))
                <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}"
                      onsubmit="return confirm('¿Aprobar {{ $cotizacion->numero }}? Se descontará el stock.')">
                    @csrf @method('PATCH')<input type="hidden" name="estado" value="aprobada">
                    <button class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Aprobar</button>
                </form>
                <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}">
                    @csrf @method('PATCH')<input type="hidden" name="estado" value="rechazada">
                    <button class="px-4 py-2 rounded-lg border border-red-200 text-red-500 text-sm hover:bg-red-50">Rechazar</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Info --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 text-sm space-y-3">
            <div><dt class="text-gray-400">Cliente</dt><dd class="text-gray-800 font-medium">{{ $cotizacion->cliente?->nombre }}</dd></div>
            <div><dt class="text-gray-400">Documento</dt><dd class="text-gray-700">{{ $cotizacion->cliente?->tipo_documento }} {{ $cotizacion->cliente?->documento ?? '—' }}</dd></div>
            <div><dt class="text-gray-400">Fecha</dt><dd class="text-gray-700">{{ $cotizacion->fecha->format('d/m/Y') }}</dd></div>
            @if ($cotizacion->validez)
                <div><dt class="text-gray-400">Válida hasta</dt><dd class="text-gray-700">{{ $cotizacion->validez->format('d/m/Y') }}</dd></div>
            @endif
            <div><dt class="text-gray-400">Vendedor</dt><dd class="text-gray-700">{{ $cotizacion->vendedor?->name ?? '—' }}</dd></div>
            @if ($cotizacion->stock_descontado)
                <div class="pt-2"><span class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs">✓ Stock descontado</span></div>
            @endif
        </div>

        {{-- Items + totales --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr>
                        <th class="px-6 py-2 font-medium">Producto</th>
                        <th class="px-6 py-2 font-medium text-center">Cant.</th>
                        <th class="px-6 py-2 font-medium text-right">Precio</th>
                        <th class="px-6 py-2 font-medium text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($cotizacion->items as $item)
                        <tr>
                            <td class="px-6 py-2">
                                <span class="text-gray-800">{{ $item->nombre }}</span>
                                @if ($item->referencia)<span class="text-xs text-gray-400"> · {{ $item->referencia }}</span>@endif
                            </td>
                            <td class="px-6 py-2 text-center">{{ $item->cantidad }}</td>
                            <td class="px-6 py-2 text-right text-gray-600">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                            <td class="px-6 py-2 text-right font-medium">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-gray-100">
                    <tr><td colspan="3" class="px-6 py-1.5 text-right text-gray-500">Subtotal</td><td class="px-6 py-1.5 text-right">${{ number_format($cotizacion->subtotal, 0, ',', '.') }}</td></tr>
                    @if ($cotizacion->descuento > 0)
                        <tr><td colspan="3" class="px-6 py-1.5 text-right text-gray-500">Descuento</td><td class="px-6 py-1.5 text-right text-red-500">−${{ number_format($cotizacion->descuento, 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="text-base"><td colspan="3" class="px-6 py-3 text-right font-semibold text-gray-700">Total</td><td class="px-6 py-3 text-right font-bold text-sweetgo-pink">${{ number_format($cotizacion->total, 0, ',', '.') }}</td></tr>
                </tfoot>
            </table>
            @if ($cotizacion->notas)
                <div class="px-6 py-4 border-t border-gray-100 text-sm">
                    <p class="text-gray-400 mb-1">Notas</p>
                    <p class="text-gray-600">{{ $cotizacion->notas }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
