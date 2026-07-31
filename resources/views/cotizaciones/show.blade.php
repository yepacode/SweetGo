@extends('layouts.admin')

@section('title', $cotizacion->numero)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('cotizaciones.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a cotizaciones</a>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $cotizacion->numero }}</h2>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cotizacion->estadoBadge() }}">{{ $cotizacion->estadoLabel() }}</span>

                {{-- Badge de pago: distingue "Pagada" vs "A crédito" (saldo pendiente) --}}
                @php
                    $totalCotHdr = (float) $cotizacion->total;
                    $aprobadoHdr = (float) $cotizacion->montoPagadoAprobado();
                    $saldoHdr = max(0, $totalCotHdr - $aprobadoHdr);
                @endphp
                @if ($cotizacion->estado === 'pagada')
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">✓ Pagada</span>
                @elseif ($cotizacion->estado === 'credito')
                    {{-- Cotización aprobada y despachada pero la plata NO entró: deuda por el total. --}}
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium"
                          title="Deuda a crédito: ${{ number_format($totalCotHdr, 0, ',', '.') }}">
                        A crédito · ${{ number_format($totalCotHdr, 0, ',', '.') }}
                    </span>
                @elseif (! in_array($cotizacion->estado, ['borrador','rechazada'], true) && $saldoHdr > 0)
                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium"
                          title="Saldo pendiente: ${{ number_format($saldoHdr, 0, ',', '.') }}">
                        A crédito · ${{ number_format($saldoHdr, 0, ',', '.') }}
                    </span>
                @endif

                {{-- Contador de ítems --}}
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-sweetgo-pink-light text-sweetgo-pink">
                    {{ $cotizacion->items->count() }} {{ $cotizacion->items->count() === 1 ? 'producto' : 'productos' }}
                </span>

                {{-- Estado del envío (tipo Miracle): pendiente/en_ruta/entregado, o "Sin envío" si está pagada sin registro --}}
                @if ($cotizacion->envio)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cotizacion->envio->estadoBadge() }}"
                          title="Envío {{ $cotizacion->envio->estadoLabel() }}{{ $cotizacion->envio->guia_numero ? ' · guía #'.$cotizacion->envio->guia_numero : '' }}">
                        🚚 {{ $cotizacion->envio->estadoLabel() }}
                    </span>
                @elseif ($cotizacion->estado === 'pagada')
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700"
                          title="Cotización pagada sin envío configurado">
                        🚚 Sin envío
                    </span>
                @endif

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

            {{-- Editar: admin en cualquier borrador; vendedor en su propio borrador. --}}
            @if ($cotizacion->puedeEditar(auth()->user()))
                <a href="{{ route('cotizaciones.edit', $cotizacion) }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">Editar</a>
            @endif

            {{-- Cambios de estado: SOLO admin (marcar enviada, rechazar). --}}
            @if (auth()->user()->hasRole('admin'))
                @if ($cotizacion->esEditable() && $cotizacion->estado === 'borrador')
                    <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}">
                        @csrf @method('PATCH')<input type="hidden" name="estado" value="enviada">
                        <button class="px-4 py-2 rounded-lg bg-sweetgo-turquoise text-white text-sm hover:opacity-90">Marcar enviada</button>
                    </form>
                @endif
                @if (in_array($cotizacion->estado, ['borrador','enviada'], true))
                    <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}"
                          onsubmit="return confirm('¿Rechazar esta cotización?')">
                        @csrf @method('PATCH')<input type="hidden" name="estado" value="rechazada">
                        <button class="px-4 py-2 rounded-lg border border-red-200 text-red-500 text-sm hover:bg-red-50">Rechazar</button>
                    </form>
                @endif
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
            <div>
                <dt class="text-gray-400">Vendedor</dt>
                <dd class="text-gray-700 flex items-center gap-2 flex-wrap">
                    <span>{{ $cotizacion->vendedor?->name ?? '—' }}</span>
                    {{-- Reasignar vendedor (solo admin, funciona en cualquier estado / con pagos). --}}
                    @if (auth()->user()->hasRole('admin') && $vendedores->isNotEmpty())
                        <div x-data="{ abierto: false }" class="relative">
                            <button type="button" @click="abierto = !abierto"
                                    class="text-xs text-sweetgo-turquoise hover:underline">
                                Reasignar
                            </button>
                            <div x-show="abierto" x-cloak @click.outside="abierto = false"
                                 class="absolute left-0 mt-1 w-64 bg-white rounded-lg shadow-lg border border-sweetgo-pink-light p-3 z-20">
                                <form method="POST" action="{{ route('cotizaciones.vendedor', $cotizacion) }}" class="space-y-2">
                                    @csrf @method('PATCH')
                                    <label class="block text-[10px] uppercase tracking-wide text-gray-500">Nuevo vendedor</label>
                                    <select name="user_id" class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                        @foreach ($vendedores as $v)
                                            <option value="{{ $v->id }}" @selected($v->id === $cotizacion->user_id)>{{ $v->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="flex justify-end gap-1">
                                        <button type="button" @click="abierto = false" class="px-2 py-1 text-xs text-gray-500 hover:underline">Cancelar</button>
                                        <button class="px-3 py-1 rounded-lg bg-sweetgo-pink text-white text-xs font-medium hover:opacity-90">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </dd>
            </div>
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
                    @if ($cotizacion->con_iva)
                        <tr>
                            <td colspan="3" class="px-6 py-1.5 text-right text-gray-500">
                                IVA {{ rtrim(rtrim(number_format($cotizacion->iva_porcentaje, 2, '.', ''), '0'), '.') }}%
                            </td>
                            <td class="px-6 py-1.5 text-right text-gray-700">+${{ number_format($cotizacion->iva_monto, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr><td colspan="4" class="px-6 py-1 text-right text-[10px] text-gray-400 uppercase tracking-wide">Sin IVA</td></tr>
                    @endif
                    <tr class="text-base"><td colspan="3" class="px-6 py-3 text-right font-semibold text-gray-700">Total</td><td class="px-6 py-3 text-right font-bold text-sweetgo-pink">${{ number_format($cotizacion->total, 0, ',', '.') }}</td></tr>
                </tfoot>
            </table>
            @if ($cotizacion->notas)
                <div class="px-6 py-4 border-t border-gray-100 text-sm">
                    <p class="text-gray-400 mb-1">Observaciones</p>
                    <p class="text-gray-600 whitespace-pre-line">{{ $cotizacion->notas }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════ PAGOS ═══════════════════════════ --}}
    @php
        $esAdmin = auth()->user()->hasRole('admin');
        $pagos = $cotizacion->pagosRecientes;
        $pagado = (float) $cotizacion->montoPagado();
        $aprobado = (float) $cotizacion->montoPagadoAprobado();
        $totalCot = (float) $cotizacion->total;
        $pct = $totalCot > 0 ? min(100, round(($pagado / $totalCot) * 100)) : 0;
        $pctAprobado = $totalCot > 0 ? min(100, round(($aprobado / $totalCot) * 100)) : 0;
        $puedeRegistrarPago = ! in_array($cotizacion->estado, ['pagada', 'rechazada'], true);
        // Para créditos, "lo que resta por cubrir" es el saldo real (excluye los pagos crédito).
        // Sin esto el form se ocultaba porque el crédito ya sumaba como "pagado" y no dejaba abonar.
        $restaPorCubrir = $cotizacion->estado === 'credito'
            ? (float) $cotizacion->saldoCredito()
            : max(0, $totalCot - $pagado);
    @endphp

    <div class="mt-6 bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">
        <div class="px-6 py-4 border-b border-sweetgo-pink-light bg-sweetgo-pink-light/30 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-800">Pagos</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Pagado: <span class="font-medium text-gray-700">${{ number_format($pagado, 0, ',', '.') }}</span>
                    · Aprobado: <span class="font-medium text-green-700">${{ number_format($aprobado, 0, ',', '.') }}</span>
                    · Total: <span class="font-medium text-sweetgo-pink">${{ number_format($totalCot, 0, ',', '.') }}</span>
                </p>
            </div>
            @if ($cotizacion->estado === 'pagada')
                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-medium">✓ Pagada</span>
            @elseif ($cotizacion->estado === 'credito')
                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700 font-medium">A crédito</span>
            @elseif ($cotizacion->estado === 'pendiente_revision_pago')
                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium">Pendiente revisión</span>
            @endif
        </div>

        {{-- ═══════════════════ Panel destacado del crédito ═══════════════════ --}}
        @if ($cotizacion->estado === 'credito')
            @php
                $saldo = $cotizacion->saldoCredito();
                $abonado = $cotizacion->totalAbonado();
                $venc = $cotizacion->proximoVencimientoCredito();
                $aging = $cotizacion->agingCredito();
                $diasV = $cotizacion->diasVencimientoCredito();
                $panelClass = match ($aging) {
                    'vencido'    => 'from-red-50 to-red-100 border-red-200',
                    'por_vencer' => 'from-amber-50 to-amber-100 border-amber-200',
                    default      => 'from-emerald-50 to-emerald-100 border-emerald-200',
                };
                $vencLabel = match (true) {
                    $aging === 'vencido'    => 'Vencido hace ' . abs($diasV) . (abs($diasV) === 1 ? ' día' : ' días'),
                    $aging === 'por_vencer' => 'Vence en ' . $diasV . ($diasV === 1 ? ' día' : ' días'),
                    $aging === 'al_dia'     => 'Al día · vence en ' . $diasV . ' días',
                    default                 => 'Sin plazo definido',
                };
                $vencColor = match ($aging) {
                    'vencido'    => 'text-red-700',
                    'por_vencer' => 'text-amber-700',
                    default      => 'text-emerald-700',
                };
            @endphp
            <div class="mx-6 mt-4 mb-1 bg-gradient-to-r {{ $panelClass }} border rounded-xl p-4">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 mb-1">Total del pedido</p>
                        <p class="text-lg font-semibold text-gray-800">${{ number_format($totalCot, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-emerald-600 mb-1">Abonado</p>
                        <p class="text-lg font-semibold text-emerald-700">${{ number_format($abonado, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-sweetgo-pink mb-1">Saldo pendiente</p>
                        <p class="text-lg font-bold text-sweetgo-pink">${{ number_format($saldo, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 mb-1">Vencimiento</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $venc?->format('d/m/Y') ?? '—' }}</p>
                        <p class="text-[11px] font-medium {{ $vencColor }}">{{ $vencLabel }}</p>
                    </div>
                </div>
                @if ($saldo > 0)
                    <p class="text-[11px] text-gray-500 mt-3 italic">💡 Para registrar un abono, usá el formulario «Registrar abono al crédito» debajo con el monto que el cliente esté pagando.</p>
                @endif

                {{-- Historial de abonos (pagos no-crédito aprobados sobre este crédito). --}}
                @php
                    $historial = $cotizacion->pagosRecientes->where('metodo', '!=', 'credito')->where('estado', 'aprobado')->values();
                @endphp
                @if ($historial->isNotEmpty())
                    <div class="mt-4 pt-3 border-t border-white/60">
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 font-medium mb-2">📜 Historial de abonos</p>
                        <ol class="relative border-l-2 border-emerald-200 pl-4 space-y-2">
                            @foreach ($historial as $abono)
                                <li class="relative">
                                    <span class="absolute -left-[22px] top-1 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                                    <div class="flex flex-wrap items-baseline justify-between gap-2 text-xs">
                                        <span class="font-semibold text-emerald-700">+${{ number_format($abono->monto, 0, ',', '.') }}</span>
                                        <span class="text-gray-500">{{ $abono->metodoLabel() }}</span>
                                        <span class="text-gray-400">{{ $abono->aprobado_at?->format('d/m/Y') ?? $abono->created_at->format('d/m/Y') }}</span>
                                    </div>
                                    @if ($abono->referencia)
                                        <p class="text-[10px] text-gray-500 italic">Ref: {{ $abono->referencia }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
        @endif

        {{-- Barra de progreso --}}
        <div class="px-6 pt-4">
            <div class="w-full h-3 rounded-full bg-gray-100 overflow-hidden relative">
                <div class="h-full bg-yellow-400/70 absolute top-0 left-0" style="width: {{ $pct }}%"></div>
                <div class="h-full bg-green-500 absolute top-0 left-0" style="width: {{ $pctAprobado }}%"></div>
            </div>
            <p class="text-[10px] text-gray-400 mt-1">
                Amarillo = pendiente de aprobación · Verde = aprobado por admin
            </p>
        </div>

        {{-- Lista de pagos --}}
        <div class="px-6 py-4 space-y-2">
            @forelse ($pagos as $pago)
                <div class="border border-gray-100 rounded-lg p-4 flex flex-wrap items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-gray-800">${{ number_format($pago->monto, 0, ',', '.') }}</span>
                            <span class="text-xs text-gray-500">·</span>
                            <span class="text-xs text-gray-600">{{ $pago->metodoLabel() }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border {{ $pago->estadoBadge() }}">{{ ucfirst($pago->estado) }}</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">
                            Registró {{ $pago->registradoPor?->name ?? '—' }} · {{ $pago->created_at->format('d/m/Y H:i') }}
                            @if ($pago->referencia) · Ref: {{ $pago->referencia }}@endif
                        </p>
                        @if ($pago->metodo === 'credito' && $pago->fecha_vencimiento)
                            @php $dias = $pago->diasParaVencer(); @endphp
                            <p class="text-[11px] mt-1">
                                <span class="text-gray-500">📅 Vence: {{ $pago->fecha_vencimiento->format('d/m/Y') }}</span>
                                @if ($pago->estaVencido())
                                    <span class="ml-1 inline-block px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-[10px] font-medium">Vencido hace {{ abs($dias) }} {{ abs($dias) === 1 ? 'día' : 'días' }}</span>
                                @elseif ($pago->estado === 'aprobado' && $dias !== null && $dias <= 7)
                                    <span class="ml-1 inline-block px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-medium">Vence en {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}</span>
                                @endif
                            </p>
                        @endif
                        @if ($pago->notas)
                            <p class="text-xs text-gray-500 mt-1 italic">{{ $pago->notas }}</p>
                        @endif
                        @if ($pago->estado === 'rechazado' && $pago->rechazo_motivo)
                            <p class="text-xs text-red-500 mt-1"><strong>Rechazo:</strong> {{ $pago->rechazo_motivo }}</p>
                        @endif
                        @if ($pago->estado === 'aprobado' && $pago->aprobado_at)
                            <p class="text-[10px] text-green-700 mt-1">✓ Aprobado por {{ $pago->aprobadoPor?->name ?? '—' }} el {{ $pago->aprobado_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($pago->comprobante)
                            <a href="{{ route('pagos.comprobante', [$cotizacion, $pago]) }}" target="_blank"
                               class="text-xs px-3 py-1.5 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise hover:bg-sweetgo-turquoise-light">Comprobante</a>
                        @endif
                        @if ($esAdmin && $pago->estado === 'pendiente')
                            <form method="POST" action="{{ route('pagos.aprobar', [$cotizacion, $pago]) }}"
                                  onsubmit="return confirm('¿Aprobar este pago?')">
                                @csrf @method('PATCH')
                                <button class="text-xs px-3 py-1.5 rounded-lg bg-green-600 text-white hover:opacity-90">✓ Aprobar</button>
                            </form>
                            <button type="button" onclick="document.getElementById('rechazar-{{ $pago->id }}').classList.toggle('hidden')"
                                    class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50">✗ Rechazar</button>
                        @endif
                    </div>

                    @if ($esAdmin && $pago->estado === 'pendiente')
                        <form id="rechazar-{{ $pago->id }}" method="POST" action="{{ route('pagos.rechazar', [$cotizacion, $pago]) }}"
                              class="w-full mt-2 hidden flex gap-2 items-center">
                            @csrf @method('PATCH')
                            <input type="text" name="motivo" required placeholder="Motivo del rechazo…"
                                   class="flex-1 rounded-lg border-gray-200 text-xs focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                            <button class="text-xs px-3 py-1.5 rounded-lg bg-red-500 text-white hover:opacity-90">Confirmar rechazo</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-xs text-gray-400 italic text-center py-4">Aún no hay pagos registrados.</p>
            @endforelse
        </div>

        {{-- Form: registrar pago (para créditos usa saldoCredito, para el resto el pendiente clásico) --}}
        @if ($puedeRegistrarPago && $restaPorCubrir > 0)
            <div x-data="{ metodo: 'efectivo' }" class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    {{ $cotizacion->estado === 'credito' ? 'Registrar abono al crédito' : 'Registrar pago' }}
                </h4>
                @if ($cotizacion->estado === 'credito')
                    <p class="text-xs text-gray-500 mb-3">
                        💡 Saldo pendiente: <strong class="text-sweetgo-pink">${{ number_format($restaPorCubrir, 0, ',', '.') }}</strong>.
                        Elegí <em>Efectivo / Transferencia / Tarjeta</em> — para transferencia y tarjeta es obligatorio adjuntar el comprobante.
                    </p>
                @endif
                @if ($errors->any())
                    <div class="mb-3 rounded-lg bg-red-50 border border-red-200 text-red-600 px-3 py-2 text-xs">
                        @foreach ($errors->all() as $e)<div>· {{ $e }}</div>@endforeach
                    </div>
                @endif
                <form method="POST" action="{{ route('pagos.store', $cotizacion) }}"
                      enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Método</label>
                        <select name="metodo" x-model="metodo" required
                                class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="credito">Crédito (venta a plazo)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Monto (COP)</label>
                        <input type="number" name="monto" min="1" step="1" required
                               max="{{ (int) $restaPorCubrir }}"
                               placeholder="Máx {{ number_format($restaPorCubrir, 0, ',', '.') }}"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Referencia</label>
                        <input type="text" name="referencia" maxlength="100"
                               placeholder="# transferencia, últimos 4…"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div x-show="metodo !== 'credito'">
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">
                            Comprobante
                            <span x-show="metodo === 'transferencia' || metodo === 'tarjeta'" class="text-sweetgo-pink">*</span>
                        </label>
                        <input type="file" name="comprobante" accept="image/*,application/pdf"
                               class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-sweetgo-pink-light file:text-sweetgo-pink">
                    </div>
                    {{-- Plazo del crédito: aparece solo cuando el método es «crédito» --}}
                    <div x-show="metodo === 'credito'" x-cloak>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Días de crédito</label>
                        <input type="number" name="dias_credito" min="1" max="365" value="{{ old('dias_credito', 30) }}"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <p class="text-[10px] text-gray-400 mt-1">Vencimiento se calcula desde hoy</p>
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Notas (opcional)</label>
                        <textarea name="notas" rows="1"
                                  class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink"></textarea>
                    </div>
                    <div class="sm:col-span-4 flex justify-end">
                        <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90">
                            Registrar pago
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════ ENVÍO ═══════════════════════════ --}}
    @if ($cotizacion->estado === 'pagada')
        @php
            $envio = $cotizacion->envio;
            $zonas = \App\Models\ZonaEnvio::where('activo', true)->orderBy('nombre')->get();
            $sucursalesCliente = $cotizacion->cliente?->sucursales ?? collect();
            $zonasJs = $zonas->mapWithKeys(fn ($z) => [$z->id => [
                'costo_base' => (float) $z->costo_base,
                'costo_kg_adicional' => (float) $z->costo_kg_adicional,
                'peso_base_kg' => (float) $z->peso_base_kg,
                'peso_maximo_kg' => $z->peso_maximo_kg ? (float) $z->peso_maximo_kg : null,
            ]]);
        @endphp

        <div class="mt-6 bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden"
             x-data="envioForm({{ Illuminate\Support\Js::from($zonasJs) }}, {{ Illuminate\Support\Js::from([
                'zona_envio_id' => $envio?->zona_envio_id,
                'peso_kg' => $envio?->peso_kg ? (float) $envio->peso_kg : null,
                'costo' => $envio ? (float) $envio->costo : null,
             ]) }})">
            <div class="px-6 py-4 border-b border-sweetgo-pink-light bg-sweetgo-turquoise-light/40 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800">Envío</h3>
                    @if ($envio)
                        <div class="flex flex-wrap items-center gap-2 mt-0.5 text-xs text-gray-500">
                            <span>Costo: <span class="font-medium text-gray-700">${{ number_format($envio->costo, 0, ',', '.') }}</span></span>
                            <span>· Peso: {{ $envio->peso_kg ? rtrim(rtrim(number_format($envio->peso_kg, 3, '.', ''), '0'), '.') . ' kg' : '—' }}</span>
                            @if ($envio->flete_asumido_sweetgo)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-sweetgo-turquoise-light text-teal-700 font-medium">
                                    ★ Flete asumido por Sweet Go
                                </span>
                            @endif
                        </div>
                    @else
                        <p class="text-xs text-gray-500 mt-0.5">Configura el envío de esta cotización.</p>
                    @endif
                </div>
                @if ($envio)
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $envio->estadoBadge() }}">{{ $envio->estadoLabel() }}</span>
                @endif
            </div>

            {{-- Datos actuales del envío (si ya existe) --}}
            @if ($envio && $envio->estado !== 'pendiente')
                <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm border-b border-gray-100">
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400">Dirección</p>
                        <p class="text-gray-700">{{ $envio->direccion ?? '—' }}{{ $envio->ciudad ? ', ' . $envio->ciudad : '' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400">Contacto</p>
                        <p class="text-gray-700">{{ $envio->contacto ?? '—' }}{{ $envio->telefono ? ' · ' . $envio->telefono : '' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400">Transportador · Guía</p>
                        <p class="text-gray-700">{{ $envio->transportador ?? '—' }}{{ $envio->guia_numero ? ' · #' . $envio->guia_numero : '' }}</p>
                        @if ($envio->guia_archivo)
                            <a href="{{ $envio->guiaUrl() }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 mt-1 text-xs text-sweetgo-pink hover:underline">
                                {{ $envio->guiaEsImagen() ? 'Ver foto de la guía' : 'Ver PDF de la guía' }} ↗
                            </a>
                        @endif
                    </div>
                    <div>
                        <p class="text-[10px] uppercase tracking-wide text-gray-400">Fecha estimada</p>
                        <p class="text-gray-700">{{ $envio->fecha_estimada?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    @if ($envio->entregado_at)
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-gray-400">Entregado</p>
                            <p class="text-green-700">{{ $envio->entregado_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Form: configurar o actualizar envío --}}
            @if (! $envio || $envio->estado === 'pendiente')
                <form method="POST" action="{{ route('envio.store', $cotizacion) }}" enctype="multipart/form-data"
                      class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Zona</label>
                        <select name="zona_envio_id" x-model="zonaId" @change="recalcular()"
                                class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                            <option value="">— Sin zona (costo manual) —</option>
                            @foreach ($zonas as $z)
                                <option value="{{ $z->id }}" @selected($envio?->zona_envio_id == $z->id)>{{ $z->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Peso (kg)</label>
                        <input type="number" name="peso_kg" x-model.number="pesoKg" @input="recalcular()"
                               min="0" step="0.001"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">
                            Costo exacto (COP) <span x-show="autoCalculado" x-cloak class="text-sweetgo-turquoise text-[9px]">auto</span>
                        </label>
                        <input type="number" name="costo" x-model.number="costo" min="0" step="1"
                               placeholder="Ej: 12500"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <p x-show="mensajeZona" x-cloak class="text-[10px] text-red-500 mt-1" x-text="mensajeZona"></p>
                        <label class="mt-2 flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="flete_asumido_sweetgo" value="1"
                                   @checked(old('flete_asumido_sweetgo', $envio?->flete_asumido_sweetgo))
                                   class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink">
                            <span>Flete asumido por Sweet Go <span class="text-gray-400">(cliente no lo paga)</span></span>
                        </label>
                    </div>

                    <div class="sm:col-span-3 border-t border-gray-100 pt-3">
                        <p class="text-[10px] uppercase tracking-wide text-gray-500 mb-2 font-medium">Dirección de entrega</p>
                        @if ($sucursalesCliente->count())
                            <div class="mb-3">
                                <label class="block text-[10px] text-gray-500 mb-1">Copiar de sucursal del cliente</label>
                                <select @change="copiarSucursal($event.target)"
                                        class="w-full sm:w-1/2 rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                    <option value="">— Elegir sucursal —</option>
                                    @foreach ($sucursalesCliente as $s)
                                        <option value="{{ $loop->index }}"
                                                data-direccion="{{ $s->direccion }}"
                                                data-ciudad="{{ $s->ciudad }}"
                                                data-contacto="{{ $s->contacto }}"
                                                data-telefono="{{ $s->telefono }}">{{ $s->nombre }}{{ $s->es_principal ? ' (principal)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Dirección</label>
                        <input type="text" name="direccion" value="{{ old('direccion', $envio?->direccion ?? $cotizacion->cliente?->direccion) }}"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Ciudad</label>
                        <input type="text" name="ciudad" value="{{ old('ciudad', $envio?->ciudad ?? $cotizacion->cliente?->ciudad) }}"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Contacto</label>
                        <input type="text" name="contacto" value="{{ old('contacto', $envio?->contacto ?? $cotizacion->cliente?->nombre) }}"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $envio?->telefono ?? $cotizacion->cliente?->telefono) }}"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Fecha estimada</label>
                        <input type="date" name="fecha_estimada" value="{{ old('fecha_estimada', $envio?->fecha_estimada?->toDateString()) }}"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                    </div>
                    <div class="sm:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Transportador</label>
                            <input type="text" name="transportador" value="{{ old('transportador', $envio?->transportador) }}"
                                   placeholder="Servientrega, TCC, propio…"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Número de guía</label>
                            <input type="text" name="guia_numero" value="{{ old('guia_numero', $envio?->guia_numero) }}"
                                   class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Guía (foto o PDF)</label>
                            <input type="file" name="guia_archivo" accept="image/jpeg,image/png,image/webp,application/pdf"
                                   class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-sweetgo-pink-light file:text-sweetgo-pink">
                            @if ($envio?->guia_archivo)
                                <p class="text-[10px] text-gray-500 mt-1">
                                    Actual:
                                    <a href="{{ $envio->guiaUrl() }}" target="_blank" rel="noopener" class="text-sweetgo-pink hover:underline">
                                        {{ $envio->guiaEsImagen() ? 'foto cargada' : 'PDF cargado' }} ↗
                                    </a>
                                    <span class="text-gray-400">· subir otro para reemplazarlo</span>
                                </p>
                            @else
                                <p class="text-[10px] text-gray-400 mt-1">JPG, PNG, WEBP o PDF · máx 8MB</p>
                            @endif
                        </div>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Notas</label>
                        <textarea name="notas" rows="1"
                                  class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">{{ old('notas', $envio?->notas) }}</textarea>
                    </div>
                    <div class="sm:col-span-3 flex justify-end">
                        <button class="px-5 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">
                            {{ $envio ? 'Actualizar envío' : 'Configurar envío' }}
                        </button>
                    </div>
                </form>
            @endif

            {{-- Cambio de estado (solo admin) --}}
            @if ($envio && auth()->user()->hasRole('admin'))
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <p class="text-[10px] uppercase tracking-wide text-gray-500 mb-2 font-medium">Estado del envío</p>
                    <form method="POST" action="{{ route('envio.estado', [$cotizacion, $envio]) }}" class="flex flex-wrap items-end gap-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="block text-[10px] text-gray-500 mb-1">Estado</label>
                            <select name="estado" required
                                    class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                                <option value="pendiente" @selected($envio->estado === 'pendiente')>Pendiente</option>
                                <option value="en_ruta" @selected($envio->estado === 'en_ruta')>En ruta</option>
                                <option value="entregado" @selected($envio->estado === 'entregado')>Entregado</option>
                                <option value="cancelado" @selected($envio->estado === 'cancelado')>Cancelado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-500 mb-1">Transportador</label>
                            <input type="text" name="transportador" value="{{ $envio->transportador }}"
                                   class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <div>
                            <label class="block text-[10px] text-gray-500 mb-1">Guía</label>
                            <input type="text" name="guia_numero" value="{{ $envio->guia_numero }}"
                                   class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        </div>
                        <button class="px-4 py-2 rounded-lg bg-sweetgo-turquoise text-white text-sm hover:opacity-90">Actualizar estado</button>
                    </form>
                </div>
            @endif
        </div>

        <script>
            function envioForm(zonas, inicial) {
                return {
                    zonas,
                    zonaId: inicial.zona_envio_id ?? '',
                    pesoKg: inicial.peso_kg ?? null,
                    costo: inicial.costo ?? null,
                    autoCalculado: false,
                    mensajeZona: '',

                    recalcular() {
                        this.mensajeZona = '';
                        if (!this.zonaId || !this.pesoKg || this.pesoKg <= 0) {
                            this.autoCalculado = false;
                            return;
                        }
                        const z = this.zonas[this.zonaId];
                        if (!z) return;
                        if (z.peso_maximo_kg && this.pesoKg > z.peso_maximo_kg) {
                            this.mensajeZona = 'El peso excede el máximo de la zona (' + z.peso_maximo_kg + ' kg). Costo manual.';
                            this.autoCalculado = false;
                            return;
                        }
                        const extra = Math.max(0, this.pesoKg - z.peso_base_kg);
                        this.costo = Math.round(z.costo_base + extra * z.costo_kg_adicional);
                        this.autoCalculado = true;
                    },

                    copiarSucursal(select) {
                        const opt = select.selectedOptions[0];
                        if (!opt || opt.value === '') return;
                        const form = select.closest('form');
                        ['direccion','ciudad','contacto','telefono'].forEach(k => {
                            const val = opt.dataset[k];
                            const input = form.querySelector(`input[name="${k}"]`);
                            if (input && val) input.value = val;
                        });
                        select.value = '';
                    },
                }
            }
        </script>
    @endif
@endsection
