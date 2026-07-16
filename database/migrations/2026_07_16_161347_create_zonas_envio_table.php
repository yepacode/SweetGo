<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas_envio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);                              // ej. "Bogotá centro"
            $table->decimal('costo_base', 12, 2)->default(0);           // costo hasta el peso base
            $table->decimal('costo_kg_adicional', 12, 2)->default(0);   // por kg extra sobre el peso base
            $table->decimal('peso_base_kg', 8, 3)->default(1);          // peso incluido en el costo base
            $table->decimal('peso_maximo_kg', 8, 3)->nullable();        // límite (null = sin límite)
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas_envio');
    }
};
