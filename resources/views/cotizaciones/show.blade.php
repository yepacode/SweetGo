@extends('layouts.admin')

@section('title', $cotizacion->numero)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('cotizaciones.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a cotizaciones</a>
            <div class="flex items-center gap-3 mt-1">
                <h2 class="text-xl font-semibold text-gray-800">{{ $cotizacion->numero }}</h2>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cotizacion->estadoBadge() }}">{{ $cotizacion->estadoLabel() }}</span>
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

            @if ($cotizacion->esEditable())
                <a href="{{ route('cotizaciones.edit', $cotizacion) }}" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">Editar</a>
            @endif

            {{-- Acciones de estado (solo si no hay pagos activos; con pagos el flujo lo maneja el bloque de Pagos) --}}
            @if ($cotizacion->esEditable() && $cotizacion->estado === 'borrador')
                <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}">
                    @csrf @method('PATCH')<input type="hidden" name="estado" value="enviada">
                    <button class="px-4 py-2 rounded-lg bg-sweetgo-turquoise text-white text-sm hover:opacity-90">Marcar enviada</button>
                </form>
            @endif
            @if (auth()->user()->hasRole('admin') && $cotizacion->esEditable() && in_array($cotizacion->estado, ['borrador','enviada']))
                <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}"
                      onsubmit="return confirm('¿Rechazar esta cotización?')">
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
                    <p class="text-gray-400 mb-1">Notas</p>
                    <p class="text-gray-600">{{ $cotizacion->notas }}</p>
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
            @elseif ($cotizacion->estado === 'pendiente_revision_pago')
                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium">Pendiente revisión</span>
            @endif
        </div>

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

        {{-- Form: registrar pago --}}
        @if ($puedeRegistrarPago && $pagado < $totalCot)
            <div x-data="{ metodo: 'efectivo' }" class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Registrar pago</h4>
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
                            <option value="credito">Crédito directo con la empresa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Monto (COP)</label>
                        <input type="number" name="monto" min="1" step="1" required
                               max="{{ (int) ($totalCot - $pagado) }}"
                               placeholder="Máx {{ number_format($totalCot - $pagado, 0, ',', '.') }}"
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
                        <p class="text-xs text-gray-500 mt-0.5">
                            Costo: <span class="font-medium text-gray-700">${{ number_format($envio->costo, 0, ',', '.') }}</span>
                            · Peso: {{ $envio->peso_kg ? rtrim(rtrim(number_format($envio->peso_kg, 3, '.', ''), '0'), '.') . ' kg' : '—' }}
                        </p>
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
                <form method="POST" action="{{ route('envio.store', $cotizacion) }}"
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
                            Costo (COP) <span x-show="autoCalculado" x-cloak class="text-sweetgo-turquoise text-[9px]">auto</span>
                        </label>
                        <input type="number" name="costo" x-model.number="costo" min="0" step="1"
                               class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <p x-show="mensajeZona" x-cloak class="text-[10px] text-red-500 mt-1" x-text="mensajeZona"></p>
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
                    <div class="sm:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
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
