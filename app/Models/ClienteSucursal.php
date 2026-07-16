<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteSucursal extends Model
{
    protected $table = 'cliente_sucursales';

    protected $fillable = [
        'cliente_id', 'nombre', 'direccion', 'ciudad',
        'telefono', 'contacto', 'notas', 'es_principal', 'orden',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
