<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega fecha de vencimiento a los pagos (útil para créditos a plazo).
 * Ej: cliente compra a 30 días → pago tipo `credito` con fecha_vencimiento = hoy + 30.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('fecha_vencimiento');
        });
    }
};
