@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <p class="text-gray-500">Bienvenido a <x-brand class="text-lg align-middle" /> — Beauty Experts</p>
        <a href="{{ route('reportes.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">Ver reportes →</a>
    </div>

    {{-- Métricas clave --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        @php
            $cards = [
                ['label' => 'Ventas del mes', 'value' => '$'.number_format($ventasMes, 0, ',', '.'), 'accent' => 'pink', 'hint' => 'Cotizaciones aprobadas'],
                ['label' => 'Cotizaciones en curso', 'value' => $cotizacionesEnCurso, 'accent' => 'turquoise', 'hint' => 'Borrador / enviadas'],
                ['label' => 'Productos activos', 'value' => $productosActivos, 'accent' => 'pink', 'hint' => 'En catálogo'],
                ['label' => 'Alertas de stock bajo', 'value' => $stockBajo, 'accent' => 'turquoise', 'hint' => 'Requieren reposición'],
            ];
        @endphp
        @foreach ($cards as $c)
            <div class="bg-white rounded-xl border border-sweetgo-pink-light p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <span class="text-sm text-gray-500">{{ $c['label'] }}</span>
                    <span class="w-2.5 h-2.5 rounded-full {{ $c['accent'] === 'pink' ? 'bg-sweetgo-pink' : 'bg-sweetgo-turquoise' }}"></span>
                </div>
                <p class="mt-2 text-3xl font-bold text-gray-800">{{ $c['value'] }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ $c['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Segunda fila de métricas --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4">
            <p class="text-xs text-gray-500">Clientes nuevos (mes)</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">{{ $clientesNuevos }}</p>
        </div>
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4">
            <p class="text-xs text-gray-500">Garantías abiertas</p>
            <p class="mt-1 text-2xl font-bold {{ $garantiasAbiertas > 0 ? 'text-amber-600' : 'text-gray-800' }}">{{ $garantiasAbiertas }}</p>
        </div>
    </div>

    {{-- Gráfico de ventas por día (últimos 30 días) --}}
    @php
        $maxTotal = max(1, collect($serie)->max('total'));
        $totalPeriodo = collect($serie)->sum('total');
        // Coordenadas SVG
        $w = 800; $h = 180; $padX = 30; $padY = 20;
        $chartW = $w - $padX * 2; $chartH = $h - $padY * 2;
        $stepX = count($serie) > 1 ? $chartW / (count($serie) - 1) : 0;
        $points = collect($serie)->map(function ($punto, $i) use ($padX, $padY, $stepX, $chartH, $maxTotal) {
            $x = $padX + $i * $stepX;
            $y = $padY + $chartH - ($punto['total'] / $maxTotal) * $chartH;
            return round($x, 1) . ',' . round($y, 1);
        })->implode(' ');
        $polygonPoints = "{$padX},".($padY + $chartH)." {$points} ".($padX + $chartW * (count($serie)-1)/max(count($serie)-1,1)).",".($padY + $chartH);
    @endphp

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-sweetgo-pink-light flex flex-wrap items-baseline justify-between gap-2">
            <div>
                <h3 class="font-semibold text-gray-700">Ventas · últimos 30 días</h3>
                <p class="text-xs text-gray-400">Cotizaciones aprobadas por día</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-sweetgo-pink">${{ number_format($totalPeriodo, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">Total del periodo</p>
            </div>
        </div>
        <div class="p-4 overflow-x-auto">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full min-w-[500px]" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="gradPink" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="#F58CD3" stop-opacity="0.35"/>
                        <stop offset="100%" stop-color="#F58CD3" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                {{-- Línea base --}}
                <line x1="{{ $padX }}" x2="{{ $padX + $chartW }}" y1="{{ $padY + $chartH }}" y2="{{ $padY + $chartH }}" stroke="#F9E9F8" stroke-width="1"/>
                {{-- Área --}}
                <polygon points="{{ $polygonPoints }}" fill="url(#gradPink)"/>
                {{-- Línea de ventas --}}
                <polyline points="{{ $points }}" fill="none" stroke="#F58CD3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                {{-- Puntos --}}
                @foreach ($serie as $i => $punto)
                    @php
                        $cx = $padX + $i * $stepX;
                        $cy = $padY + $chartH - ($punto['total'] / $maxTotal) * $chartH;
                    @endphp
                    <circle cx="{{ round($cx, 1) }}" cy="{{ round($cy, 1) }}" r="{{ $punto['total'] > 0 ? 3 : 2 }}"
                            fill="{{ $punto['total'] > 0 ? '#F58CD3' : '#F9E9F8' }}">
                        <title>{{ \Carbon\Carbon::parse($punto['fecha'])->format('d/m/Y') }} · ${{ number_format($punto['total'], 0, ',', '.') }}</title>
                    </circle>
                @endforeach
                {{-- Etiquetas eje X: primero, medio y último --}}
                @php
                    $etiquetas = [0, (int) (count($serie) / 2), count($serie) - 1];
                @endphp
                @foreach ($etiquetas as $i)
                    <text x="{{ round($padX + $i * $stepX, 1) }}" y="{{ $h - 4 }}" font-size="10" fill="#9CA3AF" text-anchor="middle">
                        {{ \Carbon\Carbon::parse($serie[$i]['fecha'])->format('d/m') }}
                    </text>
                @endforeach
            </svg>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Más vendidos --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
            <div class="px-6 py-4 border-b border-sweetgo-pink-light">
                <h3 class="font-semibold text-gray-700">Productos más vendidos</h3>
                <p class="text-xs text-gray-400">Por cotizaciones aprobadas</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-left">
                    <tr>
                        <th class="px-6 py-2 font-medium">Producto</th>
                        <th class="px-6 py-2 font-medium text-center">Unidades</th>
                        <th class="px-6 py-2 font-medium text-right">Total vendido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($masVendidos as $mv)
                        <tr>
                            <td class="px-6 py-2 text-gray-800">{{ $mv->nombre }}</td>
                            <td class="px-6 py-2 text-center font-medium">{{ $mv->unidades }}</td>
                            <td class="px-6 py-2 text-right text-gray-600">${{ number_format($mv->total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Aún no hay ventas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Alertas de stock --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
            <div class="px-6 py-4 border-b border-sweetgo-pink-light">
                <h3 class="font-semibold text-gray-700">Stock bajo</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($productosStockBajo as $p)
                    <a href="{{ route('stock.kardex', $p) }}" class="flex items-center justify-between px-6 py-3 hover:bg-sweetgo-pink-light/30">
                        <span class="text-sm text-gray-700 truncate">{{ $p->nombre }}</span>
                        <span class="ml-2 inline-block px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-xs font-medium">{{ $p->stock_actual }}/{{ $p->stock_minimo }}</span>
                    </a>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-gray-400">Todo el stock está en orden ✓</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Últimas cotizaciones --}}
    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden mt-6">
        <div class="px-6 py-4 border-b border-sweetgo-pink-light flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">Últimas cotizaciones</h3>
            <a href="{{ route('cotizaciones.index') }}" class="text-xs text-sweetgo-turquoise hover:underline">Ver todas →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-left">
                <tr>
                    <th class="px-6 py-2 font-medium">Número</th>
                    <th class="px-6 py-2 font-medium">Cliente</th>
                    <th class="px-6 py-2 font-medium">Fecha</th>
                    <th class="px-6 py-2 font-medium text-center">Estado</th>
                    <th class="px-6 py-2 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($ultimasCotizaciones as $cot)
                    <tr class="hover:bg-sweetgo-pink-light/30">
                        <td class="px-6 py-2"><a href="{{ route('cotizaciones.show', $cot) }}" class="font-medium text-sweetgo-pink hover:underline">{{ $cot->numero }}</a></td>
                        <td class="px-6 py-2 text-gray-700">{{ $cot->cliente?->nombre ?? '—' }}</td>
                        <td class="px-6 py-2 text-gray-500">{{ $cot->fecha->format('d/m/Y') }}</td>
                        <td class="px-6 py-2 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $cot->estadoBadge() }}">{{ ucfirst($cot->estado) }}</span></td>
                        <td class="px-6 py-2 text-right font-medium">${{ number_format($cot->total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Sin cotizaciones aún.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
