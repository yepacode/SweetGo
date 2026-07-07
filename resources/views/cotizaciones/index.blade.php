@extends('layouts.admin')

@section('title', 'Cotizaciones')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Cotizaciones</h2>
            <p class="text-sm text-gray-500">{{ $cotizaciones->total() }} cotizaciones</p>
        </div>
        <a href="{{ route('cotizaciones.create') }}"
           class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90 transition">
            + Nueva cotización
        </a>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar número o cliente…"
               class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        <select name="estado" onchange="this.form.submit()" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Todos los estados</option>
            @foreach (['borrador','enviada','aprobada','rechazada'] as $e)
                <option value="{{ $e }}" @selected(request('estado')===$e)>{{ ucfirst($e) }}</option>
            @endforeach
        </select>
        <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700">Filtrar</button>
    </form>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Número</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Vendedor</th>
                        <th class="px-4 py-3 font-medium text-center">Estado</th>
                        <th class="px-4 py-3 font-medium text-right">Total</th>
                        <th class="px-4 py-3 font-medium text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cotizaciones as $cot)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3"><a href="{{ route('cotizaciones.show', $cot) }}" class="font-medium text-sweetgo-pink hover:underline">{{ $cot->numero }}</a></td>
                            <td class="px-4 py-3 text-gray-700">{{ $cot->cliente?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $cot->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $cot->vendedor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $cot->estadoBadge() }}">{{ ucfirst($cot->estado) }}</span>
                                @if ($cot->esta_vencida)
                                    <span class="ml-1 inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600" title="La validez pasó el {{ $cot->validez?->format('d/m/Y') }}">Vencida</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">${{ number_format($cot->total, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('cotizaciones.pdf', $cot) }}" target="_blank" class="text-xs text-sweetgo-turquoise hover:underline">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No hay cotizaciones aún.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $cotizaciones->links() }}</div>
@endsection
