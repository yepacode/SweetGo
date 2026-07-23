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
                <div class="title">DIRECTORIO DE CLIENTES</div>
                <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr></table>
    </div>
    <div class="content">
        <p class="muted">Total de clientes: <strong>{{ $clientes->count() }}</strong></p>
        <table class="data">
            <thead><tr>
                <th>Nombre</th><th>Documento</th><th>Teléfono</th><th>Correo</th><th>Ciudad</th><th>Lista</th><th class="center">Cotiz.</th>
            </tr></thead>
            <tbody>
                @foreach ($clientes as $c)
                    <tr>
                        <td>{{ $c->nombre }}</td>
                        <td>{{ $c->tipo_documento }} {{ $c->documento }}</td>
                        <td>{{ $c->telefono }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->ciudad }}</td>
                        <td>{{ $c->listaPrecio?->nombre ?? '—' }}</td>
                        <td class="center">{{ $c->cotizaciones_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="footer">Sweet Go · Beauty Experts &nbsp;|&nbsp; Directorio de clientes</div>
    </div>
</body>
</html>
