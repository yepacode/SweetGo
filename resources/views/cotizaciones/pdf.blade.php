<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #374151; font-size: 12px; }
        .pink { color: #F58CD3; }
        .teal { color: #81D1D1; }
        .header { padding: 24px 32px; border-bottom: 3px solid #F58CD3; }
        .brand { font-size: 28px; font-weight: bold; }
        .brand .star { color: #81D1D1; }
        .muted { color: #9CA3AF; }
        .content { padding: 24px 32px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .items th { background: #F9E9F8; color: #6B7280; text-align: left; padding: 8px 10px; font-size: 11px; }
        .items td { padding: 8px 10px; border-bottom: 1px solid #F1F1F1; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals td { padding: 4px 10px; }
        .total-row td { font-size: 15px; font-weight: bold; color: #F58CD3; border-top: 2px solid #F58CD3; padding-top: 8px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; background: #C3EAEA; color: #0f766e; font-size: 11px; }
        .box { background: #FBF7FB; border: 1px solid #F9E9F8; border-radius: 8px; padding: 12px 14px; }
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #eee; font-size: 10px; color: #9CA3AF; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand"><span class="pink">Sweet</span> <span class="star">&#10022;</span> <span class="pink">Go</span></div>
                    <div class="teal" style="letter-spacing:3px; font-size:10px;">BEAUTY EXPERTS</div>
                </td>
                <td class="right">
                    <div style="font-size:18px; font-weight:bold; color:#6B7280;">COTIZACIÓN</div>
                    <div class="pink" style="font-size:16px; font-weight:bold;">{{ $cotizacion->numero }}</div>
                    <div class="muted">{{ $cotizacion->fecha->format('d/m/Y') }}</div>
                    <div style="margin-top:4px;"><span class="badge">{{ ucfirst($cotizacion->estado) }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table>
            <tr>
                <td width="55%" style="vertical-align:top;">
                    <div class="muted" style="font-size:10px; text-transform:uppercase; margin-bottom:4px;">Cliente</div>
                    <div class="box">
                        <strong>{{ $cotizacion->cliente?->nombre }}</strong><br>
                        @if ($cotizacion->cliente?->documento){{ $cotizacion->cliente->tipo_documento }} {{ $cotizacion->cliente->documento }}<br>@endif
                        @if ($cotizacion->cliente?->telefono)Tel: {{ $cotizacion->cliente->telefono }}<br>@endif
                        @if ($cotizacion->cliente?->email){{ $cotizacion->cliente->email }}<br>@endif
                        @if ($cotizacion->cliente?->direccion){{ $cotizacion->cliente->direccion }}{{ $cotizacion->cliente->ciudad ? ', '.$cotizacion->cliente->ciudad : '' }}@endif
                    </div>
                </td>
                <td width="5%"></td>
                <td width="40%" style="vertical-align:top;">
                    <div class="muted" style="font-size:10px; text-transform:uppercase; margin-bottom:4px;">Detalles</div>
                    <div class="box">
                        <table class="meta">
                            <tr><td class="muted">Fecha:</td><td class="right">{{ $cotizacion->fecha->format('d/m/Y') }}</td></tr>
                            @if ($cotizacion->validez)<tr><td class="muted">Válida hasta:</td><td class="right">{{ $cotizacion->validez->format('d/m/Y') }}</td></tr>@endif
                            <tr><td class="muted">Vendedor:</td><td class="right">{{ $cotizacion->vendedor?->name ?? '—' }}</td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items" style="margin-top:20px;">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="center" width="10%">Cant.</th>
                    <th class="right" width="18%">Precio unit.</th>
                    <th class="right" width="20%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cotizacion->items as $item)
                    <tr>
                        <td>{{ $item->nombre }}@if ($item->referencia)<span class="muted"> · {{ $item->referencia }}</span>@endif</td>
                        <td class="center">{{ $item->cantidad }}</td>
                        <td class="right">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                        <td class="right">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-top:12px;">
            <tr>
                <td width="60%" style="vertical-align:top;">
                    @if ($cotizacion->notas)
                        <div class="muted" style="font-size:10px; text-transform:uppercase; margin-bottom:4px;">Notas</div>
                        <div style="font-size:11px;">{{ $cotizacion->notas }}</div>
                    @endif
                </td>
                <td width="40%">
                    <table class="totals">
                        <tr><td class="muted">Subtotal</td><td class="right">${{ number_format($cotizacion->subtotal, 0, ',', '.') }}</td></tr>
                        @if ($cotizacion->descuento > 0)
                            <tr><td class="muted">Descuento</td><td class="right">−${{ number_format($cotizacion->descuento, 0, ',', '.') }}</td></tr>
                        @endif
                        <tr class="total-row"><td>TOTAL</td><td class="right">${{ number_format($cotizacion->total, 0, ',', '.') }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer">
            Sweet Go · Beauty Experts &nbsp;|&nbsp; Cotización generada el {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; Valores en pesos colombianos (COP)
        </div>
    </div>
</body>
</html>
