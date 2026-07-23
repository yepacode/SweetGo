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
                <div class="title">REPORTE DE INVENTARIO</div>
                <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr></table>
    </div>
    <div class="content">
        <p class="muted">Total de productos: <strong>{{ $productos->count() }}</strong> &nbsp;·&nbsp; Unidades en stock: <strong>{{ number_format($totalUnidades, 0, ',', '.') }}</strong></p>
        <table class="data">
            <thead><tr>
                <th>Producto</th><th>Ref.</th><th>Categoría</th>
                <th class="right">Precio</th><th class="center">Stock</th><th class="center">Mínimo</th><th class="center">Estado</th>
            </tr></thead>
            <tbody>
                @foreach ($productos as $p)
                    <tr>
                        <td>{{ $p->nombre }}</td>
                        <td>{{ $p->referencia }}</td>
                        <td>{{ $p->categoria?->nombre ?? '—' }}</td>
                        <td class="right">${{ number_format($p->precio, 0, ',', '.') }}</td>
                        <td class="center">{{ $p->stock_actual }}</td>
                        <td class="center">{{ $p->stock_minimo }}</td>
                        <td class="center">{{ $p->activo ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="footer">Sweet Go · Beauty Experts &nbsp;|&nbsp; Reporte de inventario &nbsp;|&nbsp; Valores en COP</div>
    </div>
</body>
</html>
