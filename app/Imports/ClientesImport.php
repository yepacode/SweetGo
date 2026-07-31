<?php

namespace App\Imports;

use App\Models\Cliente;
use App\Models\ListaPrecio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa clientes desde Excel/CSV.
 * Columnas: nombre*, tipo_documento, documento, telefono, email, ciudad, direccion, lista_precio, notas
 *
 * - Coincidencia por «documento» (si viene): actualiza; si no existe o no viene documento, crea.
 * - Lista de precios: busca por nombre exacto, por slug o por id. Si no viene, usa la predeterminada.
 * - user_id = admin que importa. La bitácora se dispara solo si Auth::check() (RegistraBitacora).
 */
class ClientesImport implements ToCollection, WithHeadingRow
{
    public int $creados = 0;
    public int $actualizados = 0;
    public int $omitidos = 0;
    public array $errores = []; // [fila_num => mensaje]

    /** @var array<string, int> mapa slug/nombre_lower → id */
    private array $mapaListas = [];
    private ?int $listaDefault = null;

    public function __construct()
    {
        foreach (ListaPrecio::where('activo', true)->get() as $l) {
            $this->mapaListas[Str::slug($l->nombre, '_')] = $l->id;
            $this->mapaListas[mb_strtolower(trim($l->nombre))] = $l->id;
            $this->mapaListas[(string) $l->id] = $l->id;
        }
        $this->listaDefault = ListaPrecio::predeterminada()?->id;
    }

    private function resolverLista($valor): ?int
    {
        $v = trim((string) $valor);
        if ($v === '') return $this->listaDefault;
        $slug = Str::slug($v, '_');
        return $this->mapaListas[$slug]
            ?? $this->mapaListas[mb_strtolower($v)]
            ?? $this->mapaListas[$v]
            ?? $this->listaDefault;
    }

    public function collection($rows): void
    {
        $userId = Auth::id();

        foreach ($rows as $idx => $row) {
            $filaNum = $idx + 2; // header cuenta como 1
            $nombre = trim((string) ($row['nombre'] ?? ''));
            if ($nombre === '') {
                $this->omitidos++;
                continue;
            }

            $documento = trim((string) ($row['documento'] ?? '')) ?: null;
            $listaId = $this->resolverLista($row['lista_precio'] ?? null);

            $payload = [
                'nombre' => $nombre,
                'tipo_documento' => trim((string) ($row['tipo_documento'] ?? '')) ?: null,
                'telefono' => trim((string) ($row['telefono'] ?? '')) ?: null,
                'email' => trim((string) ($row['email'] ?? '')) ?: null,
                'ciudad' => trim((string) ($row['ciudad'] ?? '')) ?: null,
                'direccion' => trim((string) ($row['direccion'] ?? '')) ?: null,
                'notas' => trim((string) ($row['notas'] ?? '')) ?: null,
                'lista_precio_id' => $listaId,
                'activo' => true,
            ];

            // Cada fila en su propio try/catch: si una falla (documento dup ya creado en la misma
            // corrida, valor inválido, etc.) el import continúa con las siguientes en vez de cortarse.
            try {
                $existente = $documento ? Cliente::where('documento', $documento)->first() : null;

                if ($existente) {
                    $existente->update(array_filter($payload, fn ($v) => $v !== null && $v !== ''));
                    $this->actualizados++;
                } else {
                    Cliente::create(array_merge($payload, [
                        'documento' => $documento,
                        'user_id' => $userId,
                    ]));
                    $this->creados++;
                }
            } catch (\Illuminate\Database\QueryException $e) {
                $motivo = str_contains($e->getMessage(), '1062') ? 'documento duplicado' : 'error de BD';
                $this->errores[$filaNum] = "Fila {$filaNum} («{$nombre}»): {$motivo}.";
            } catch (\Throwable $e) {
                $this->errores[$filaNum] = "Fila {$filaNum} («{$nombre}»): " . $e->getMessage();
            }
        }
    }
}
