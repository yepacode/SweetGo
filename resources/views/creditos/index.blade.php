@extends('layouts.admin')

@section('title', 'Cuentas por cobrar')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Cuentas por cobrar</h2>
            <p class="text-sm text-gray-500">Cotizaciones vendidas a crédito con saldo pendiente</p>
        </div>
    </div>

    {{-- ═══════════════════ KPIs ═══════════════════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-4">
            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">Total por cobrar</p>
            <p class="text-2xl font-bold text-sweetgo-pink">${{ number_format($stats['total_saldo'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $stats['cuantas'] }} {{ $stats['cuantas'] === 1 ? 'crédito' : 'créditos' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-red-200 p-4">
            <p class="text-[10px] uppercase tracking-wide text-red-500 mb-1">Vencido</p>
            <p class="text-2xl font-bold text-red-600">${{ number_format($stats['total_vencido'], 0, ',', '.') }}</p>
            <p class="text-xs text-red-500 mt-1">{{ $stats['cuentas_vencidas'] }} {{ $stats['cuentas_vencidas'] === 1 ? 'cuenta' : 'cuentas' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-amber-200 p-4">
            <p class="text-[10px] uppercase tracking-wide text-amber-600 mb-1">Por vencer (≤7 días)</p>
            <p class="text-2xl font-bold text-amber-600">${{ number_format($stats['total_por_vencer'], 0, ',', '.') }}</p>
            <p class="text-xs text-amber-500 mt-1">Requiere seguimiento</p>
        </div>
        <div class="bg-white rounded-xl border border-emerald-200 p-4">
            <p class="text-[10px] uppercase tracking-wide text-emerald-600 mb-1">Al día</p>
            <p class="text-2xl font-bold text-emerald-600">
                ${{ number_format($stats['total_al_dia'], 0, ',', '.') }}
            </p>
            <p class="text-xs text-emerald-500 mt-1">
                Dentro del plazo
                @if ($stats['total_sin_plazo'] > 0)
                    · <span class="text-gray-400">+${{ number_format($stats['total_sin_plazo'], 0, ',', '.') }} sin plazo</span>
                @endif
            </p>
        </div>
    </div>

    {{-- ═══════════════════ Filtros ═══════════════════ --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar número o cliente…"
               class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        <select name="aging" onchange="this.form.submit()"
                class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
            <option value="">Todos los estados</option>
            <option value="vencido"    @selected(request('aging')==='vencido')>🔴 Vencidos</option>
            <option value="por_vencer" @selected(request('aging')==='por_vencer')>🟠 Por vencer (≤7 días)</option>
            <option value="al_dia"     @selected(request('aging')==='al_dia')>🟢 Al día</option>
            <option value="sin_plazo"  @selected(request('aging')==='sin_plazo')>⚪ Sin plazo</option>
        </select>
        @if ($clientesConCredito->isNotEmpty())
            <select name="cliente" onchange="this.form.submit()"
                    class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                <option value="">Todos los clientes</option>
                @foreach ($clientesConCredito as $cl)
                    <option value="{{ $cl->id }}" @selected(request('cliente')==$cl->id)>{{ $cl->nombre }}</option>
                @endforeach
            </select>
        @endif
        <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700">Filtrar</button>
        @if (request('aging') || request('cliente') || request('buscar'))
            <a href="{{ route('creditos.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Limpiar</a>
        @endif
    </form>

    {{-- ═══════════════════ Tabla ═══════════════════ --}}
    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Cotización</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        @if ($esAdmin)
                            <th class="px-4 py-3 font-medium">Vendedor</th>
                        @endif
                        <th class="px-4 py-3 font-medium text-right">Total</th>
                        <th class="px-4 py-3 font-medium text-right">Abonado</th>
                        <th class="px-4 py-3 font-medium text-right">Saldo</th>
                        <th class="px-4 py-3 font-medium">Vencimiento</th>
                        <th class="px-4 py-3 font-medium text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($todos as $c)
                        @php
                            $aging = $c->agingCredito();
                            $dias = $c->diasVencimientoCredito();
                            $venc = $c->proximoVencimientoCredito();
                            $badgeClass = match ($aging) {
                                'vencido'    => 'bg-red-100 text-red-700',
                                'por_vencer' => 'bg-amber-100 text-amber-700',
                                'al_dia'     => 'bg-emerald-100 text-emerald-700',
                                default      => 'bg-gray-100 text-gray-500',
                            };
                            $badgeLabel = match ($aging) {
                                'vencido'    => 'Vencido hace ' . abs($dias) . ($dias === -1 ? ' día' : ' días'),
                                'por_vencer' => 'Vence en ' . $dias . ($dias === 1 ? ' día' : ' días'),
                                'al_dia'     => 'Vence en ' . $dias . ' días',
                                default      => 'Sin plazo',
                            };
                        @endphp
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('cotizaciones.show', $c) }}" class="font-medium text-sweetgo-pink hover:underline">{{ $c->numero }}</a>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $c->fecha->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $c->cliente?->nombre ?? '—' }}
                                @if ($c->cliente?->telefono)<p class="text-[11px] text-gray-400 mt-0.5">{{ $c->cliente->telefono }}</p>@endif
                            </td>
                            @if ($esAdmin)
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $c->vendedor?->name ?? '—' }}</td>
                            @endif
                            <td class="px-4 py-3 text-right text-gray-700">${{ number_format($c->total, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600">${{ number_format($c->totalAbonado(), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-sweetgo-pink">${{ number_format($c->saldoCredito(), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $venc ? $venc->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $esAdmin ? 8 : 7 }}" class="px-4 py-10 text-center text-gray-400">
                            No hay créditos {{ request('aging') || request('cliente') || request('buscar') ? 'que coincidan con los filtros' : 'activos' }}. 🌸
                        </td></tr>
                    @endforelse
                </tbody>
                @if ($todos->isNotEmpty())
                    <tfoot class="bg-sweetgo-pink-light/30">
                        <tr>
                            <td colspan="{{ $esAdmin ? 3 : 2 }}" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Totales</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-700">${{ number_format($todos->sum('total'), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600">${{ number_format($todos->sum(fn ($c) => $c->totalAbonado()), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-bold text-sweetgo-pink">${{ number_format($todos->sum(fn ($c) => $c->saldoCredito()), 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
