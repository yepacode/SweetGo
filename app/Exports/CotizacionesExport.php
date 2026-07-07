<?php

namespace App\Exports;

use App\Models\Cotizacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CotizacionesExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function __construct(public ?string $desde = null, public ?string $hasta = null) {}

    public function collection()
    {
        return Cotizacion::with(['cliente', 'vendedor'])
            ->when($this->desde, fn ($q) => $q->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha', '<=', $this->hasta))
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return ['Número', 'Cliente', 'Fecha', 'Vendedor', 'Estado', 'Subtotal', 'Descuento', 'Total'];
    }

    public function map($cot): array
    {
        return [
            $cot->numero,
            $cot->cliente?->nombre ?? '—',
            $cot->fecha?->format('d/m/Y'),
            $cot->vendedor?->name ?? '—',
            ucfirst($cot->estado),
            (float) $cot->subtotal,
            (float) $cot->descuento,
            (float) $cot->total,
        ];
    }
}
