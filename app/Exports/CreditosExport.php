<?php

namespace App\Exports;

use App\Models\Cotizacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/**
 * Reporte de cuentas por cobrar: cotizaciones en estado `credito` con saldo pendiente,
 * abonos aplicados, aging y vencimiento. Filtrable por cliente y por aging.
 */
class CreditosExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function __construct(private ?int $clienteId = null, private ?string $aging = null) {}

    public function collection()
    {
        $todos = Cotizacion::enCredito()
            ->with(['cliente', 'vendedor', 'pagos'])
            ->when($this->clienteId, fn ($q) => $q->where('cliente_id', $this->clienteId))
            ->orderByDesc('id')
            ->get();

        if ($this->aging && in_array($this->aging, ['vencido', 'por_vencer', 'al_dia', 'sin_plazo'], true)) {
            $todos = $todos->filter(fn ($c) => $c->agingCredito() === $this->aging)->values();
        }

        return $todos;
    }

    public function headings(): array
    {
        return ['Número', 'Fecha', 'Cliente', 'Vendedor', 'Total', 'Abonado', 'Saldo', 'Vencimiento', 'Días', 'Estado'];
    }

    public function map($c): array
    {
        $aging = $c->agingCredito();
        $dias = $c->diasVencimientoCredito();
        $venc = $c->proximoVencimientoCredito();
        $estadoLabel = match ($aging) {
            'vencido'    => "Vencido ({$dias}d)",
            'por_vencer' => "Por vencer ({$dias}d)",
            'al_dia'     => "Al día ({$dias}d)",
            default      => 'Sin plazo',
        };

        return [
            $c->numero,
            $c->fecha->format('d/m/Y'),
            $c->cliente?->nombre ?? '—',
            $c->vendedor?->name ?? '—',
            (float) $c->total,
            (float) $c->totalAbonado(),
            (float) $c->saldoCredito(),
            $venc?->format('d/m/Y') ?? '—',
            $dias ?? '—',
            $estadoLabel,
        ];
    }
}
