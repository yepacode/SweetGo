<?php

namespace Database\Seeders;

use App\Models\Bitacora;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EnlaceCatalogo;
use App\Models\Garantia;
use App\Models\ListaPrecio;
use App\Models\PrecioProducto;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de prueba integrales para QA: usuarios, clientes, cotizaciones y
 * garantías en todos sus estados, stock variado, precios por lista y bitácora.
 */
class QaSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------- Usuarios / perfiles ----------------
        $vendedores = [];
        foreach ([['Laura Gómez', 'laura@sweetgo.com'], ['Andrés Ruiz', 'andres@sweetgo.com']] as [$nombre, $email]) {
            $u = User::firstOrCreate(['email' => $email], ['name' => $nombre, 'password' => Hash::make('password')]);
            $u->syncRoles(['vendedor']);
            $vendedores[] = $u;
        }
        $admin = User::where('email', 'admin@sweetgo.com')->first();
        $usuarios = array_merge([$admin], $vendedores);

        // ---------------- Precios por lista (mayorista/super distintos) ----------------
        $normal = ListaPrecio::where('slug', 'normal-publico')->first();
        $may = ListaPrecio::where('slug', 'mayorista')->first();
        $sup = ListaPrecio::where('slug', 'super-mayorista')->first();
        foreach (Producto::all() as $p) {
            PrecioProducto::updateOrCreate(['producto_id' => $p->id, 'lista_precio_id' => $may->id], ['precio' => max(1, round($p->precio * 0.90))]);
            PrecioProducto::updateOrCreate(['producto_id' => $p->id, 'lista_precio_id' => $sup->id], ['precio' => max(1, round($p->precio * 0.80))]);
        }

        // ---------------- Stock variado (sano / bajo / agotado) ----------------
        foreach (Producto::orderBy('id')->get() as $i => $p) {
            if ($p->stock_actual > 0) {
                continue;
            }
            if ($i % 5 === 0) {
                // dejar en 0 -> alerta de agotado
            } elseif ($i % 3 === 0) {
                $p->registrarMovimiento('entrada', 3, 'Carga inicial QA (stock bajo)');
            } else {
                $p->registrarMovimiento('entrada', rand(40, 150), 'Carga inicial QA');
            }
        }

        // ---------------- Clientes ----------------
        $ciudades = ['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Bucaramanga'];
        $clientesData = [
            ['Distribuidora Glam', 'NIT', '901112223', $may->id, true],
            ['Peluquería Estilo Único', 'CC', '43556677', $normal->id, true],
            ['Mega Belleza SAS', 'NIT', '901998877', $sup->id, true],
            ['Salón Divas', 'CC', '52334455', $normal->id, true],
            ['Cosméticos del Valle', 'NIT', '805443322', $may->id, true],
            ['Cliente Inactivo Demo', 'CC', '11223344', $normal->id, false],
        ];
        $clientes = [];
        foreach ($clientesData as $cd) {
            $clientes[] = Cliente::firstOrCreate(['nombre' => $cd[0]], [
                'tipo_documento' => $cd[1],
                'documento' => $cd[2],
                'telefono' => '30' . rand(10000000, 99999999),
                'email' => Str::slug($cd[0]) . '@correo.com',
                'ciudad' => $ciudades[array_rand($ciudades)],
                'direccion' => 'Calle ' . rand(1, 200) . ' # ' . rand(1, 90) . '-' . rand(1, 60),
                'lista_precio_id' => $cd[3],
                'activo' => $cd[4],
            ]);
        }
        $clientes[] = Cliente::where('nombre', 'Salón Bella Vista')->first();

        // ---------------- Cotizaciones en varios estados ----------------
        $conStock = Producto::where('stock_actual', '>', 12)->get();
        $secuencia = ['borrador', 'enviada', 'aprobada', 'rechazada', 'aprobada', 'enviada', 'borrador', 'aprobada'];
        foreach ($secuencia as $k => $estado) {
            $cliente = $clientes[$k % count($clientes)];
            if (! $cliente) {
                continue;
            }
            $vend = $vendedores[$k % count($vendedores)];
            $cot = Cotizacion::create([
                'numero' => Cotizacion::siguienteNumero(),
                'cliente_id' => $cliente->id,
                'user_id' => $vend->id,
                'estado' => 'borrador',
                'fecha' => Carbon::now()->subDays(rand(0, 45)),
                'descuento' => rand(0, 1) ? rand(1, 5) * 1000 : 0,
            ]);
            foreach ($conStock->random(rand(2, 4)) as $prod) {
                $precio = $prod->precioEnLista($cliente->lista_precio_id);
                $cant = rand(1, 4);
                $cot->items()->create([
                    'producto_id' => $prod->id,
                    'nombre' => $prod->nombre,
                    'referencia' => $prod->referencia,
                    'cantidad' => $cant,
                    'precio_unitario' => $precio,
                    'subtotal' => $precio * $cant,
                ]);
            }
            $cot->recalcularTotales();

            if ($estado === 'aprobada') {
                try {
                    $cot->aprobar();
                    $cot->update(['aprobada_at' => Carbon::now()->subDays(rand(0, 20))]);
                } catch (\Exception $e) {
                    $cot->update(['estado' => 'aprobada', 'aprobada_at' => now()]);
                }
            } elseif ($estado !== 'borrador') {
                $cot->update(['estado' => $estado]);
            }
        }

        // ---------------- Garantías en todos los estados ----------------
        $secuenciaG = ['recibido', 'en_gestion', 'resuelto', 'cerrado', 'recibido', 'resuelto'];
        $problemas = ['defecto de fábrica', 'llegó dañado en el envío', 'no funciona correctamente', 'pieza faltante', 'material defectuoso'];
        $evidencia = 'garantias/demo_evidencia.png';
        foreach ($secuenciaG as $k => $eg) {
            $cliente = $clientes[$k % count($clientes)];
            $prod = Producto::inRandomOrder()->first();
            $g = Garantia::create([
                'numero' => Garantia::siguienteNumero(),
                'cliente_id' => $cliente->id,
                'producto_id' => $prod->id,
                'descripcion' => 'El cliente reporta: ' . $problemas[array_rand($problemas)] . '.',
                'estado' => $eg,
                'user_id' => $usuarios[$k % count($usuarios)]->id,
                'fecha_recibido' => Carbon::now()->subDays(rand(1, 30)),
                'solucion' => in_array($eg, ['resuelto', 'cerrado']) ? 'Se gestionó el cambio del producto con el proveedor y se entregó al cliente.' : null,
                'fecha_cierre' => $eg === 'cerrado' ? Carbon::now()->subDays(rand(1, 5)) : null,
            ]);
            if ($k % 2 === 0 && file_exists(storage_path('app/public/' . $evidencia))) {
                $g->documentos()->create(['ruta' => $evidencia, 'nombre_original' => 'evidencia.png', 'es_imagen' => true, 'user_id' => $admin->id]);
            }
        }

        // ---------------- Enlace de catálogo extra ----------------
        EnlaceCatalogo::firstOrCreate(['titulo' => 'Mayoristas'], ['activo' => true, 'visitas' => rand(8, 60)]);

        // ---------------- Bitácora demo (inserción directa para poblar el historial) ----------------
        $acciones = [
            ['creó', 'Creó Cliente «Distribuidora Glam»', 'Cliente'],
            ['actualizó', 'Actualizó Producto «Cepillo Mágico» (precio)', 'Producto'],
            ['creó', 'Creó Cotización «COT-0003»', 'Cotizacion'],
            ['movimiento', 'Registró entrada de 50 en «Chumi Big» (stock 0→50)', 'MovimientoStock'],
            ['eliminó', 'Eliminó Categoría «Temporal»', 'Categoria'],
            ['actualizó', 'Actualizó Garantía «GAR-0002» (estado, solucion)', 'Garantia'],
        ];
        foreach ($acciones as $k => [$accion, $desc, $modelo]) {
            Bitacora::create([
                'user_id' => $usuarios[$k % count($usuarios)]->id,
                'accion' => $accion,
                'modelo' => $modelo,
                'descripcion' => $desc,
                'ip' => '127.0.0.1',
                'created_at' => Carbon::now()->subDays(rand(0, 10))->subHours(rand(0, 23)),
                'updated_at' => now(),
            ]);
        }
    }
}
