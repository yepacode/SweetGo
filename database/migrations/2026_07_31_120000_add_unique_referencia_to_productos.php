<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice UNIQUE en productos.referencia (nullable → MySQL permite múltiples NULL).
 * IMPORTANTE: correr `php artisan sweetgo:productos-deduplicar --force` ANTES,
 * si no la creación del índice falla por duplicados existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unique('referencia', 'productos_referencia_unique');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_referencia_unique');
        });
    }
};
