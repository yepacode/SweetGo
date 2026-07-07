<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');                 // creó, actualizó, eliminó, cambió estado, inició sesión…
            $table->string('modelo')->nullable();      // Producto, Cliente, Cotizacion…
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->string('descripcion');             // texto legible
            $table->json('cambios')->nullable();       // campos modificados
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['modelo', 'modelo_id']);
            $table->index('accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
