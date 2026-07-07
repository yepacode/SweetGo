<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GarantiaDocumento extends Model
{
    protected $table = 'garantia_documentos';

    protected $fillable = ['garantia_id', 'ruta', 'nombre_original', 'es_imagen', 'user_id'];

    protected $casts = [
        'es_imagen' => 'boolean',
    ];

    public function garantia()
    {
        return $this->belongsTo(Garantia::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->ruta);
    }
}
