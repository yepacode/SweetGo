<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        // [nombre, referencia, precio, categoría]
        $items = [
            ['Cepillo Alpargata', '4001', 8500, 'Cepillos'],
            ['Cepillo Mágico', '4017', 8500, 'Cepillos'],
            ['Cepillo Esqueleto', '4018', 9000, 'Cepillos'],
            ['Cepillo Ovalado', '4019', 9000, 'Cepillos'],
            ['Cepillo Redondo Points', '4016', 7200, 'Cepillos'],
            ['Cepillo Masajeador Ovalado Dúo', '4003', 5300, 'Cepillos'],
            ['Cepillo Masajeador Paleta', '4013', 4800, 'Cepillos'],
            ['Cepillo Masajeador Spiral', '4014', 4300, 'Cepillos'],
            ['Cepillo Pulpo Redondo Masajeador', '4005', 3200, 'Cepillos'],
            ['Cepillo Pulidor', '4024', 1450, 'Cepillos'],

            ['Rulo Adhesivo Medium', '4007', 4900, 'Rulos'],
            ['Rulo Adhesivo Big', '4006', 6200, 'Rulos'],
            ['Rulo Tapa Small', '4009', 2800, 'Rulos'],
            ['Rulo Tapa Medium', '4008', 3000, 'Rulos'],

            ['Chumi Small', '4010', 5800, 'Chumis'],
            ['Chumi Medium', '4011', 6200, 'Chumis'],
            ['Chumi Big', '4012', 6500, 'Chumis'],

            ['Dona Mediana', '4022', 1000, 'Accesorios de peinado'],
            ['Gorro de Aluminio', '4021', 1200, 'Accesorios de peinado'],
            ['Gorro de Silicona', '4023', 6000, 'Accesorios de peinado'],
            ['Difusor para Secador', '4034', 10900, 'Accesorios de peinado'],

            ['Guante de Nitrilo Talla S', '4055-S', 19500, 'Guantes'],
            ['Guante de Nitrilo Talla M', '4055-M', 19500, 'Guantes'],
            ['Guante de Nitrilo Talla L', '4055-L', 19500, 'Guantes'],

            ['Pinza Elite plástico x 6', '4025', 3500, 'Pinzas y caimanes'],
            ['Pinza Elite Cocodrilo x 6', '4030', 4500, 'Pinzas y caimanes'],
            ['Pinza Elite Metálica x 12', '4033', 2800, 'Pinzas y caimanes'],
            ['Caiman Peluquero Pequeño', '4027', 3500, 'Pinzas y caimanes'],
            ['Caiman Peluquero Mediano Blanco y Negro', '4028', 3500, 'Pinzas y caimanes'],

            ['Brocha de tinte Negra', '4031', 450, 'Tinturado'],
            ['Brocha de tinte Escarcha', '4032', 1200, 'Tinturado'],
            ['Peinilla Diente de León', '4026', 1200, 'Tinturado'],
            ['Peinilla Milimétrica Master', '4037', 1100, 'Tinturado'],
            ['Peinilla Doble Milimétrica', '4039', 1100, 'Tinturado'],
            ['Peinilla de Corte', '4038', 1100, 'Tinturado'],
            ['Peinilla Cabo Metálico', '4040', 1100, 'Tinturado'],
            ['Peinilla Wahl', '4036', 1100, 'Tinturado'],
            ['Peinilla Mojarra', '4056', 750, 'Tinturado'],

            ['Pestaña Rusa 10 mm 20 pelitos', '4058', 3900, 'Pestañas y cejas'],
            ['Pestaña Rusa 12 mm 20 pelitos', '4058', 3900, 'Pestañas y cejas'],
            ['Pestaña Rusa 14 mm 20 pelitos', '4058', 3900, 'Pestañas y cejas'],
            ['Encrespador de Pestañas Corazón', '4060', 3200, 'Pestañas y cejas'],
            ['Encrespador de Pestañas Piña', '4051', 3600, 'Pestañas y cejas'],
            ['Encrespador de Pestaña + Pomo', '4061', 4500, 'Pestañas y cejas'],
            ['Pincel Ceja Dúo', '4053', 2400, 'Pestañas y cejas'],
            ['Pincel de Cejas Tradicional', '4054', 900, 'Pestañas y cejas'],

            ['Depilador Pastel', '4047', 2100, 'Depilación'],
            ['Depilador Escarcha', '4046', 3500, 'Depilación'],

            ['Almohada Limpiadora', '4072', 3500, 'Maquillaje y aplicadores'],
            ['Borla x 2', '4042', 1300, 'Maquillaje y aplicadores'],
            ['Borla x 3', '4041', 1400, 'Maquillaje y aplicadores'],
            ['Bellota Marmoleada', '4048', 2900, 'Maquillaje y aplicadores'],
            ['Bellota x 1', '4044', 1000, 'Maquillaje y aplicadores'],
            ['Bellota x 3', '4045', 3000, 'Maquillaje y aplicadores'],

            ['Kit Brocha Profesional x 10 + Encrespador', '4049', 42000, 'Kits y sets'],
            ['Set Brocha Profesional x 5', '4050', 19900, 'Kits y sets'],
            ['Set Brocha Profesional x 3 + Borla', '4052', 7500, 'Kits y sets'],
        ];

        // Crear categorías únicas
        $categorias = [];
        foreach (array_unique(array_column($items, 3)) as $nombre) {
            $categorias[$nombre] = Categoria::firstOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre, 'activo' => true]
            );
        }

        // Crear productos (idempotente por nombre)
        foreach ($items as [$nombre, $referencia, $precio, $categoria]) {
            Producto::firstOrCreate(
                ['nombre' => $nombre],
                [
                    'categoria_id' => $categorias[$categoria]->id,
                    'referencia' => $referencia,
                    'precio' => $precio,
                    'stock_actual' => 0,
                    'stock_minimo' => 5,
                    'activo' => true,
                ]
            );
        }
    }
}
