<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteEmail extends Model
{
    protected $table = 'cliente_emails';

    protected $fillable = ['cliente_id', 'etiqueta', 'email', 'orden'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
