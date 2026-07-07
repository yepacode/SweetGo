<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class Garantia extends Model
{
    use RegistraBitacora;

    protected $table = 'garantias';

    protected $fillable = [
        'numero', 'cliente_id', 'producto_id', 'producto_nombre', 'descripcion',
        'estado', 'solucion', 'user_id', 'fecha_recibido', 'fecha_cierre',
    ];

    protected $casts = [
        'fecha_recibido' => 'date',
        'fecha_cierre' => 'date',
    ];

    /** Estados en orden del flujo. */
    public const ESTADOS = [
        'recibido' => 'Recibido',
        'en_gestion' => 'En gestión',
        'resuelto' => 'Resuelto',
        'cerrado' => 'Cerrado',
    ];

    /** Estados que se consideran "abiertos" (para el indicador por cliente). */
    public const ABIERTOS = ['recibido', 'en_gestion', 'resuelto'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documentos()
    {
        return $this->hasMany(GarantiaDocumento::class);
    }

    /**
     * Crea una garantía con número correlativo GAR-0001 de forma SEGURA ante concurrencia.
     */
    public static function crearConNumero(array $atributos): self
    {
        for ($intento = 0; $intento < 5; $intento++) {
            $ultimoNum = static::orderByDesc('id')->value('numero');
            $base = $ultimoNum ? (int) preg_replace('/\D/', '', $ultimoNum) : 0;
            $atributos['numero'] = 'GAR-' . str_pad((string) ($base + 1 + $intento), 4, '0', STR_PAD_LEFT);

            try {
                return static::create($atributos);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] !== 1062) {
                    throw $e;
                }
            }
        }
        throw new \RuntimeException('No se pudo generar un número único de garantía.');
    }

    public static function siguienteNumero(): string
    {
        $ultimo = static::orderByDesc('id')->value('numero');
        $n = $ultimo ? ((int) preg_replace('/\D/', '', $ultimo)) + 1 : 1;

        return 'GAR-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    protected function bitacoraEtiqueta(): string
    {
        return 'Garantía';
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function getEsAbiertaAttribute(): bool
    {
        return in_array($this->estado, self::ABIERTOS, true);
    }

    public function estadoBadge(): string
    {
        return match ($this->estado) {
            'recibido'   => 'bg-sweetgo-pink-light text-sweetgo-pink',
            'en_gestion' => 'bg-amber-100 text-amber-700',
            'resuelto'   => 'bg-sweetgo-turquoise-light text-teal-700',
            'cerrado'    => 'bg-green-100 text-green-700',
            default      => 'bg-gray-100 text-gray-600',
        };
    }

    /** Nombre del producto (relación o texto libre). */
    public function getProductoDisplayAttribute(): string
    {
        return $this->producto?->nombre ?? $this->producto_nombre ?? '—';
    }
}
