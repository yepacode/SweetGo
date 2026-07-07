<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">@include('reportes.pdf._estilos')</head>
<body>
    <div class="header">
        <table style="width:100%"><tr>
            <td>
                <div class="brand"><span class="pink">Sweet</span> <span class="star">&#10022;</span> <span class="pink">Go</span></div>
                <div class="teal">BEAUTY EXPERTS</div>
            </td>
            <td class="right">
                <div class="title">REPORTE DE COTIZACIONES</div>
                <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr></table>
    </div>
    <div class="content">
        <p class="muted">
            Total cotizaciones: <strong>{{ $cotizaciones->count() }}</strong> &nbsp;·&nbsp;
            Vendido (aprobadas): <strong>${{ number_format($total, 0, ',', '.') }}</strong>
            @if ($desde ?? false)&nbsp;·&nbsp; Desde: <strong>{{ $desde }}</strong>@endif
            @if ($hasta ?? false)&nbsp;·&nbsp; Hasta: <strong>{{ $hasta }}</strong>@endif
        </p>
        <table class="data">
            <thead><tr>
                <th>Número</th><th>Cliente</th><th>Fecha</th><th>Vendedor</th><th class="center">Estado</th>
                <th class="right">Subtotal</th><th class="right">Descuento</th><th class="right">Total</th>
            </tr></thead>
            <tbody>
                @foreach ($cotizaciones as $c)
                    <tr>
                        <td>{{ $c->numero }}</td>
                        <td>{{ $c->cliente?->nombre ?? '—' }}</td>
                        <td>{{ $c->fecha?->format('d/m/Y') }}</td>
                        <td>{{ $c->vendedor?->name ?? '—' }}</td>
                        <td class="center">{{ ucfirst($c->estado) }}</td>
                        <td class="right">${{ number_format($c->subtotal, 0, ',', '.') }}</td>
                        <td class="right">${{ number_format($c->descuento, 0, ',', '.') }}</td>
                        <td class="right">${{ number_format($c->total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="footer">Sweet Go · Beauty Experts &nbsp;|&nbsp; Reporte de cotizaciones &nbsp;|&nbsp; Valores en COP</div>
    </div>
</body>
</html>
