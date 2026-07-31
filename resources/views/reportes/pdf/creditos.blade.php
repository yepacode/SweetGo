<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8">@include('reportes.pdf._estilos')</head>
<body>
    <div class="header">
        <table style="width:100%"><tr>
            <td>
                <img src="{{ public_path('img/sweetgo-logo.png') }}" alt="Sweet Go" style="height: 50px; width: auto;">
            </td>
            <td class="right">
                <div class="title">CUENTAS POR COBRAR</div>
                <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr></table>
    </div>
    <div class="content">
        <p class="muted">
            {{ $todos->count() }} {{ $todos->count() === 1 ? 'crédito' : 'créditos' }} ·
            Total por cobrar: <strong style="color:#F58CD3">${{ number_format($totalSaldo, 0, ',', '.') }}</strong> ·
            Vencido: <strong style="color:#C43E3E">${{ number_format($totalVencido, 0, ',', '.') }}</strong>
        </p>
        <table class="data">
            <thead><tr>
                <th>Cotización</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th>
                <th class="right">Total</th><th class="right">Abonado</th><th class="right">Saldo</th>
                <th>Vencimiento</th><th>Estado</th>
            </tr></thead>
            <tbody>
                @foreach ($todos as $c)
                    @php
                        $aging = $c->agingCredito();
                        $dias = $c->diasVencimientoCredito();
                        $venc = $c->proximoVencimientoCredito();
                        $estadoLabel = match($aging) {
                            'vencido'    => "Vencido {$dias}d",
                            'por_vencer' => "Por vencer {$dias}d",
                            'al_dia'     => "Al día {$dias}d",
                            default      => 'Sin plazo',
                        };
                        $color = match($aging) {
                            'vencido' => '#C43E3E',
                            'por_vencer' => '#B78500',
                            'al_dia' => '#0f766e',
                            default => '#9CA3AF',
                        };
                    @endphp
                    <tr>
                        <td><strong>{{ $c->numero }}</strong></td>
                        <td>{{ $c->fecha->format('d/m/Y') }}</td>
                        <td>{{ $c->cliente?->nombre ?? '—' }}</td>
                        <td>{{ $c->vendedor?->name ?? '—' }}</td>
                        <td class="right">${{ number_format($c->total, 0, ',', '.') }}</td>
                        <td class="right">${{ number_format($c->totalAbonado(), 0, ',', '.') }}</td>
                        <td class="right"><strong style="color:#F58CD3">${{ number_format($c->saldoCredito(), 0, ',', '.') }}</strong></td>
                        <td>{{ $venc?->format('d/m/Y') ?? '—' }}</td>
                        <td style="color:{{ $color }}">{{ $estadoLabel }}</td>
                    </tr>
                @endforeach
                @if ($todos->isEmpty())
                    <tr><td colspan="9" class="center muted" style="padding:20px">Sin créditos que coincidan con los filtros.</td></tr>
                @endif
            </tbody>
        </table>
        <div class="footer">Sweet Go · Beauty Experts &nbsp;|&nbsp; Cuentas por cobrar generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
