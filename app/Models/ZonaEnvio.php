<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaEnvio extends Model
{
    protected $table = 'zonas_envio';

    protected $fillable = [
        'nombre', 'costo_base', 'costo_kg_adicional', 'peso_base_kg', 'peso_maximo_kg', 'notas', 'activo',
    ];

    protected $casts = [
        'costo_base' => 'decimal:2',
        'costo_kg_adicional' => 'decimal:2',
        'peso_base_kg' => 'decimal:3',
        'peso_maximo_kg' => 'decimal:3',
        'activo' => 'boolean',
    ];

    public function envios()
    {
        return $this->hasMany(Envio::class, 'zona_envio_id');
    }

    /**
     * Costo estimado para un peso dado, según las reglas de la zona.
     * peso ≤ peso_base → costo_base.
     * peso > peso_base → costo_base + (peso - peso_base) * costo_kg_adicional.
     * Si peso_maximo_kg está definido y peso lo excede, retorna null (no cubre).
     */
    public function calcularCosto(float $pesoKg): ?float
    {
        if ($pesoKg < 0) {
            return null;
        }
        if ($this->peso_maximo_kg && $pesoKg > (float) $this->peso_maximo_kg) {
            return null;
        }
        $base = (float) $this->costo_base;
        $extra = max(0, $pesoKg - (float) $this->peso_base_kg);

        return $base + ($extra * (float) $this->costo_kg_adicional);
    }
}
