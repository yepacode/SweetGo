<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precio_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variante_producto_id')->constrained('variantes_producto')->cascadeOnDelete();
            $table->foreignId('lista_precio_id')->constrained('lista_precios')->cascadeOnDelete();
            $table->decimal('precio', 12, 2);
            $table->timestamps();

            $table->unique(['variante_producto_id', 'lista_precio_id'], 'variante_lista_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precio_variantes');
    }
};
