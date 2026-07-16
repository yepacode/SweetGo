<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variantes_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre', 120);                 // Ej: "Rosa", "Talla M", "500 ml"
            $table->string('referencia', 100)->unique();   // Referencia por variante (única global)
            $table->unsignedInteger('stock_actual')->default(0);
            $table->unsignedInteger('stock_minimo')->default(0);
            $table->unsignedInteger('stock_maximo')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['producto_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variantes_producto');
    }
};
