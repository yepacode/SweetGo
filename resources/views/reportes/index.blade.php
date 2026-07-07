@extends('layouts.admin')

@section('title', 'Reportes')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Reportes y exportables</h2>
        <p class="text-sm text-gray-500">Descarga tu información en Excel o PDF para análisis o respaldo.</p>
    </div>

    {{-- Filtro de fechas (aplica al reporte de Cotizaciones) --}}
    <form method="GET" class="bg-white rounded-xl border border-sweetgo-pink-light p-4 mb-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ $desde }}" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta }}" class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>
        <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700">Aplicar</button>
        @if ($desde || $hasta)
            <a href="{{ route('reportes.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50">Limpiar</a>
        @endif
        <p class="w-full text-xs text-gray-400 mt-2">El filtro aplica al reporte de Cotizaciones (Excel y PDF). Inventario y Clientes siempre son totales.</p>
    </form>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4"><p class="text-xs text-gray-500">Productos</p><p class="mt-1 text-2xl font-bold text-gray-800">{{ $resumen['productos'] }}</p></div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4"><p class="text-xs text-gray-500">Clientes</p><p class="mt-1 text-2xl font-bold text-gray-800">{{ $resumen['clientes'] }}</p></div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4"><p class="text-xs text-gray-500">Cotizaciones</p><p class="mt-1 text-2xl font-bold text-gray-800">{{ $resumen['cotizaciones'] }}</p></div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4"><p class="text-xs text-gray-500">Unidades stock</p><p class="mt-1 text-2xl font-bold text-gray-800">{{ number_format($resumen['unidades_stock'], 0, ',', '.') }}</p></div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4"><p class="text-xs text-gray-500">Aprobadas periodo</p><p class="mt-1 text-2xl font-bold text-gray-800">{{ $resumen['cotizaciones_periodo'] }}</p></div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4"><p class="text-xs text-gray-500">Ventas periodo</p><p class="mt-1 text-2xl font-bold text-sweetgo-pink">${{ number_format($resumen['ventas_periodo'], 0, ',', '.') }}</p></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @php
            $reportes = [
                ['titulo' => 'Inventario', 'desc' => 'Productos, precios y stock actual.', 'excel' => 'reportes.inventario.excel', 'pdf' => 'reportes.inventario.pdf', 'icon' => 'M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7M4 7l2-3h12l2 3M9 12h6'],
                ['titulo' => 'Cotizaciones', 'desc' => 'Todas las cotizaciones con su estado y total.', 'excel' => 'reportes.cotizaciones.excel', 'pdf' => 'reportes.cotizaciones.pdf', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['titulo' => 'Clientes', 'desc' => 'Directorio con contacto y lista de precios.', 'excel' => 'reportes.clientes.excel', 'pdf' => 'reportes.clientes.pdf', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
            ];
        @endphp
        @php
            $rango = array_filter(['desde' => $desde, 'hasta' => $hasta]);
        @endphp
        @foreach ($reportes as $r)
            @php
                // El filtro de fechas solo aplica a cotizaciones
                $params = $r['titulo'] === 'Cotizaciones' ? $rango : [];
            @endphp
            <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 flex flex-col">
                <div class="w-11 h-11 rounded-lg bg-sweetgo-pink-light flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-sweetgo-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $r['icon'] }}"/></svg>
                </div>
                <h3 class="font-semibold text-gray-800">{{ $r['titulo'] }}</h3>
                <p class="text-sm text-gray-500 mt-1 flex-1">
                    {{ $r['desc'] }}
                    @if ($r['titulo'] === 'Cotizaciones' && ($desde || $hasta))
                        <span class="block mt-1 text-xs text-sweetgo-pink">Filtrado: {{ $desde ?: '—' }} → {{ $hasta ?: '—' }}</span>
                    @endif
                </p>
                <div class="flex gap-2 mt-4">
                    <a href="{{ route($r['excel'], $params) }}" class="flex-1 text-center px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:opacity-90">Excel</a>
                    <a href="{{ route($r['pdf'], $params) }}" target="_blank" class="flex-1 text-center px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">PDF</a>
                </div>
            </div>
        @endforeach
    </div>
@endsection
