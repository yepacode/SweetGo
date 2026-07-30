<?php

namespace App\Exports;

use App\Models\ListaPrecio;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Plantilla XLSX branded para importar clientes con lista de precios.
 * Columnas: nombre*, tipo_documento, documento, telefono, email, ciudad, direccion, lista_precio, notas
 */
class PlantillaClientesExport implements FromArray, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Clientes';
    }

    public function headings(): array
    {
        return ['nombre', 'tipo_documento', 'documento', 'telefono', 'email', 'ciudad', 'direccion', 'lista_precio', 'notas'];
    }

    public function array(): array
    {
        $listas = ListaPrecio::where('activo', true)->orderBy('nombre')->pluck('nombre')->all();
        $listaEjemplo = $listas[0] ?? 'Normal Publico';
        $listaMay = collect($listas)->first(fn ($n) => stripos($n, 'mayor') !== false) ?? $listaEjemplo;

        return [
            ['Salón Bella Vista',   'NIT', '900123456-1', '3001234567', 'contacto@bellavista.co', 'Bogotá',       'Calle 45 # 12-34',   $listaMay,     'Cliente mayorista'],
            ['Juanita Peluquería',  'CC',  '52123456',    '3009876543', 'juanita@example.com',    'Bucaramanga',  'Cra 27 # 45-10',     $listaEjemplo, ''],
            ['Estilo Total SAS',    'NIT', '901555222-4', '6017778888', 'ventas@estilototal.co',  'Medellín',     'Cll 10 # 23-45',     $listaEjemplo, 'Facturación mensual'],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 32, 'B' => 14, 'C' => 18, 'D' => 16, 'E' => 32, 'F' => 18, 'G' => 34, 'H' => 22, 'I' => 30];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ult = 'I';
                $listas = ListaPrecio::where('activo', true)->orderBy('nombre')->pluck('nombre')->implode(' | ');
                if ($listas === '') $listas = '(usa la predeterminada si dejas vacío)';

                $sheet->insertNewRowBefore(1, 4);

                $sheet->mergeCells("A1:{$ult}1");
                $sheet->setCellValue('A1', 'Sweet Go · Plantilla de importación de clientes');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF58CD3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                $sheet->mergeCells("A2:{$ult}2");
                $sheet->setCellValue('A2', 'Completa una fila por cliente. Si viene documento, se actualiza el existente; si no, se crea uno nuevo.');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF00807F']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC3EAEA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $sheet->mergeCells("A3:{$ult}3");
                $sheet->setCellValue('A3', 'Obligatorio: nombre. Listas disponibles para «lista_precio»: ' . $listas);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['argb' => 'FF666666']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9E9F8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getRowDimension(4)->setRowHeight(6);

                $sheet->getStyle("A5:{$ult}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF58CD3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(28);

                $sheet->getStyle("A6:{$ult}8")->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['argb' => 'FF8B4A85']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDF7FB']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_DASHED, 'color' => ['argb' => 'FFF3B8DE']]],
                ]);

                $sheet->freezePane('A6');
                $sheet->setShowGridlines(false);
            },
        ];
    }
}
