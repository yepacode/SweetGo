<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use RegistraBitacora;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre', 'tipo_documento', 'documento', 'telefono',
        'email', 'direccion', 'ciudad', 'notas', 'activo', 'lista_precio_id', 'user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: solo los clientes visibles para el usuario dado.
     * Admin ve todos; vendedor solo los suyos (o legacy sin dueño).
     */
    public function scopeVisiblesPara($query, ?\App\Models\User $user)
    {
        if (! $user || $user->hasRole('admin')) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function listaPrecio()
    {
        return $this->belongsTo(ListaPrecio::class, 'lista_precio_id');
    }

    /** Id de la lista del cliente, o la predeterminada si no tiene. */
    public function listaPrecioId(): ?int
    {
        return $this->lista_precio_id ?? ListaPrecio::predeterminada()?->id;
    }

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class);
    }

    public function garantias()
    {
        return $this->hasMany(Garantia::class);
    }

    /** Garantías abiertas (para el indicador visible en la ficha). */
    public function garantiasAbiertas()
    {
        return $this->hasMany(Garantia::class)->whereIn('estado', Garantia::ABIERTOS);
    }
}
