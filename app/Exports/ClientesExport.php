<?php

namespace App\Exports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ClientesExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function collection()
    {
        return Cliente::with('listaPrecio')->withCount('cotizaciones')->orderBy('nombre')->get();
    }

    public function headings(): array
    {
        return ['Nombre', 'Tipo doc.', 'Documento', 'Teléfono', 'Correo', 'Ciudad', 'Lista de precios', 'Cotizaciones', 'Estado'];
    }

    public function map($c): array
    {
        return [
            $c->nombre,
            $c->tipo_documento,
            $c->documento,
            $c->telefono,
            $c->email,
            $c->ciudad,
            $c->listaPrecio?->nombre ?? '—',
            $c->cotizaciones_count,
            $c->activo ? 'Activo' : 'Inactivo',
        ];
    }
}
