<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteTelefono extends Model
{
    protected $table = 'cliente_telefonos';

    protected $fillable = ['cliente_id', 'etiqueta', 'numero', 'orden'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
