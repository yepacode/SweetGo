<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventarioExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function collection()
    {
        return Producto::with('categoria')->orderBy('nombre')->get();
    }

    public function headings(): array
    {
        return ['Producto', 'Referencia', 'Categoría', 'Precio', 'Stock actual', 'Stock mínimo', 'Estado'];
    }

    public function map($producto): array
    {
        return [
            $producto->nombre,
            $producto->referencia,
            $producto->categoria?->nombre ?? 'Sin categoría',
            (float) $producto->precio,
            $producto->stock_actual,
            $producto->stock_minimo,
            $producto->activo ? 'Activo' : 'Inactivo',
        ];
    }
}
