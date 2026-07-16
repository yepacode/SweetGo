<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes de la Ronda 2A tras el reporte del QA agent:
 * - CRÍTICO #1: expandir enum de cotizaciones.estado para incluir 'pendiente_revision_pago' y 'pagada'.
 * - MEDIO #4: unique(cotizacion_id) en envios (blindar hasOne).
 * - MEDIO #6: nullOnDelete en pagos.user_id (aliniar con Cliente/Cotizacion/Garantia).
 * - BAJO #8: índice compuesto pagos(cotizacion_id, estado).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Enum de cotizaciones. En MySQL cambiar un enum requiere modificar la columna.
        DB::statement("ALTER TABLE cotizaciones MODIFY COLUMN estado ENUM(
            'borrador','enviada','pendiente_revision_pago','aprobada','pagada','rechazada'
        ) NOT NULL DEFAULT 'borrador'");

        // 2) Envios: un solo envío por cotización.
        Schema::table('envios', function (Blueprint $table) {
            $table->unique('cotizacion_id');
        });

        // 3) Pagos: user_id (registrador) → nullable + nullOnDelete + índice compuesto.
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE pagos MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['cotizacion_id', 'estado'], 'pagos_cotizacion_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex('pagos_cotizacion_estado_idx');
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('envios', function (Blueprint $table) {
            $table->dropUnique(['cotizacion_id']);
        });

        DB::statement("ALTER TABLE cotizaciones MODIFY COLUMN estado ENUM(
            'borrador','enviada','aprobada','rechazada'
        ) NOT NULL DEFAULT 'borrador'");
    }
};
