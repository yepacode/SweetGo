<?php

namespace App\Exports;

use App\Models\ListaPrecio;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Plantilla XLSX de importación de productos con branding Sweet Go y una columna
 * dinámica por cada lista de precios activa (rosa #F58CD3 headers, turquesa acentos).
 *
 * Columnas fijas: nombre, referencia, categoria, precio, stock, stock_minimo
 * Columnas dinámicas: una por cada lista de precios (encabezado = "precio_" + slug de la lista)
 */
class PlantillaProductosExport implements FromArray, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    /** @var \Illuminate\Support\Collection<int, \App\Models\ListaPrecio> */
    private $listas;

    public function __construct()
    {
        $this->listas = ListaPrecio::where('activo', true)
            ->orderByDesc('es_publica')->orderByDesc('es_predeterminada')->orderBy('nombre')
            ->get();
    }

    public function title(): string
    {
        return 'Productos';
    }

    public function headings(): array
    {
        $fijos = ['nombre', 'referencia', 'categoria', 'precio', 'stock', 'stock_minimo', 'stock_maximo'];
        $listas = $this->listas->map(fn ($l) => 'precio_' . Str::slug($l->nombre, '_'))->all();
        return array_merge($fijos, $listas);
    }

    public function array(): array
    {
        // 3 filas de ejemplo. Los precios por lista rellenan cada columna dinámica
        // con un valor razonable (mayorista ~ público × 0.85, super mayorista × 0.70).
        $ejemplos = [
            ['Cepillo Alpargata',         '4001', 'Cepillos',           8500, 10, 5, 100],
            ['Rulo Adhesivo Medium',      '4011', 'Rulos',              6500, 20, 5, 200],
            ['Pinza Elite Cocodrilo x 6', '4026', 'Pinzas y caimanes', 12000, 15, 5, 150],
        ];
        foreach ($ejemplos as &$fila) {
            $base = $fila[3];
            foreach ($this->listas as $lista) {
                if ($lista->es_publica || $lista->es_predeterminada) {
                    $fila[] = $base;                                 // Público = precio base
                } elseif (stripos($lista->nombre, 'super') !== false) {
                    $fila[] = (int) round($base * 0.70);             // Super mayorista
                } elseif (stripos($lista->nombre, 'mayor') !== false) {
                    $fila[] = (int) round($base * 0.85);             // Mayorista
                } else {
                    $fila[] = $base;                                 // Cualquier otra
                }
            }
        }
        return $ejemplos;
    }

    public function columnWidths(): array
    {
        $anchos = [
            'A' => 42, // nombre
            'B' => 14, // referencia
            'C' => 24, // categoria
            'D' => 14, // precio
            'E' => 10, // stock
            'F' => 14, // stock_minimo
            'G' => 14, // stock_maximo
        ];
        // Ancho para las columnas de listas (empezando en H).
        $letra = 'H';
        foreach ($this->listas as $_) {
            $anchos[$letra] = 20;
            $letra++;
        }
        return $anchos;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Última columna: G fija (7) + una por cada lista.
                $ultCol = Coordinate::stringFromColumnIndex(7 + $this->listas->count());

                // Insertar 4 filas arriba para el banner y las instrucciones.
                $sheet->insertNewRowBefore(1, 4);

                // Fila 1: título de marca (fondo rosa Sweet Go)
                $sheet->mergeCells("A1:{$ultCol}1");
                $sheet->setCellValue('A1', 'Sweet Go · Plantilla de importación de productos');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF58CD3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                // Fila 2: subtítulo (turquesa suave)
                $sheet->mergeCells("A2:{$ultCol}2");
                $sheet->setCellValue('A2', 'Completa una fila por producto. La categoría se crea automáticamente si no existe. Los precios por lista son opcionales.');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF00807F']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC3EAEA']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Fila 3: leyenda de campos obligatorios/opcionales
                $sheet->mergeCells("A3:{$ultCol}3");
                $sheet->setCellValue('A3', 'Obligatorios: nombre y precio. Opcionales: referencia, categoria, stock, stock_minimo y precios por lista.');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['argb' => 'FF666666']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9E9F8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // Fila 4 en blanco (separador)
                $sheet->getRowDimension(4)->setRowHeight(6);

                // Fila 5 (encabezados fijos A:F) — rosa fuerte con texto blanco
                $sheet->getStyle('A5:G5')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF58CD3']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
                ]);
                // Encabezados dinámicos de listas — turquesa (para diferenciarlos visualmente)
                if ($this->listas->isNotEmpty()) {
                    $sheet->getStyle("H5:{$ultCol}5")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF00807F']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC3EAEA']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
                    ]);
                }
                $sheet->getRowDimension(5)->setRowHeight(32);

                // Filas 6-8 (ejemplo) — fondo rosa MUY claro con borde punteado
                $sheet->getStyle("A6:{$ultCol}8")->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['argb' => 'FF8B4A85']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFDF7FB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_DASHED, 'color' => ['argb' => 'FFF3B8DE']]],
                ]);
                // Formato COP para el precio base y todas las columnas de precios por lista
                $sheet->getStyle('D6:D8')->getNumberFormat()->setFormatCode('"$"#,##0');
                $sheet->getStyle('E6:G8')->getNumberFormat()->setFormatCode('#,##0');
                if ($this->listas->isNotEmpty()) {
                    $sheet->getStyle("H6:{$ultCol}8")->getNumberFormat()->setFormatCode('"$"#,##0');
                    $sheet->getStyle("H6:{$ultCol}8")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                $sheet->getStyle('D6:G8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getRowDimension(6)->setRowHeight(22);
                $sheet->getRowDimension(7)->setRowHeight(22);
                $sheet->getRowDimension(8)->setRowHeight(22);

                // Congelar los encabezados
                $sheet->freezePane('A6');

                // Ocultar líneas de cuadrícula para dar la sensación "diseñada".
                $sheet->setShowGridlines(false);
            },
        ];
    }
}
