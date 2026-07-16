<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada item de cotización puede apuntar a una variante específica (opcional).
 * Se guarda además el nombre y referencia congelados por si la variante cambia luego.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->foreignId('variante_producto_id')->nullable()->after('producto_id')
                ->constrained('variantes_producto')->nullOnDelete();
            $table->string('variante_nombre', 120)->nullable()->after('variante_producto_id');
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->dropForeign(['variante_producto_id']);
            $table->dropColumn(['variante_producto_id', 'variante_nombre']);
        });
    }
};
