<?php

namespace App\Models\Concerns;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

/**
 * Registra automáticamente en la bitácora las acciones crear/editar/eliminar
 * de un modelo, solo cuando hay un usuario autenticado (acciones desde la plataforma).
 *
 * El modelo puede definir:
 *   protected array $bitacoraIgnorar = ['stock_actual'];  // campos que no disparan log al actualizar
 */
trait RegistraBitacora
{
    public static function bootRegistraBitacora(): void
    {
        static::created(fn ($model) => $model->registrarEnBitacora('creó'));
        static::updated(fn ($model) => $model->registrarEnBitacora('actualizó'));
        static::deleted(fn ($model) => $model->registrarEnBitacora('eliminó'));
    }

    protected function registrarEnBitacora(string $accion): void
    {
        // Solo registramos acciones hechas por un usuario autenticado en la plataforma.
        if (! Auth::check()) {
            return;
        }

        $ignorar = array_merge(
            ['updated_at', 'created_at'],
            property_exists($this, 'bitacoraIgnorar') ? $this->bitacoraIgnorar : []
        );

        $cambios = null;
        if ($accion === 'actualizó') {
            $cambios = array_values(array_diff(array_keys($this->getChanges()), $ignorar));
            if (empty($cambios)) {
                return; // no cambió nada relevante
            }
        }

        $modelo = class_basename($this);
        $nombre = $this->bitacoraNombre();
        $descripcion = ucfirst($accion) . " {$this->bitacoraEtiqueta()} «{$nombre}»";
        if ($cambios) {
            $descripcion .= ' (' . implode(', ', $cambios) . ')';
        }

        Bitacora::registrar($accion, $descripcion, $modelo, $this->getKey(), $cambios);
    }

    /** Nombre legible del registro (nombre/numero/titulo/token o #id). */
    protected function bitacoraNombre(): string
    {
        foreach (['nombre', 'numero', 'titulo', 'token'] as $campo) {
            if (! empty($this->attributes[$campo])) {
                return (string) $this->attributes[$campo];
            }
        }

        return '#' . $this->getKey();
    }

    /** Etiqueta legible del modelo (se puede sobrescribir). */
    protected function bitacoraEtiqueta(): string
    {
        return class_basename($this);
    }
}
