<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Añade el valor 'credito' al enum de cotizaciones.estado.
 * Se dispara cuando el admin aprueba un pago tipo `credito` que cubre el total:
 * la mercancía se despacha pero la plata todavía no entró (deuda vigente).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE cotizaciones MODIFY COLUMN estado ENUM(
            'borrador','enviada','pendiente_revision_pago','aprobada','pagada','credito','rechazada'
        ) NOT NULL DEFAULT 'borrador'");
    }

    public function down(): void
    {
        // Antes de revertir, cualquier fila en 'credito' pasa a 'pagada' (comportamiento previo).
        DB::table('cotizaciones')->where('estado', 'credito')->update(['estado' => 'pagada']);

        DB::statement("ALTER TABLE cotizaciones MODIFY COLUMN estado ENUM(
            'borrador','enviada','pendiente_revision_pago','aprobada','pagada','rechazada'
        ) NOT NULL DEFAULT 'borrador'");
    }
};
