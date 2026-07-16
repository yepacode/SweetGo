<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('zona_envio_id')->nullable()->constrained('zonas_envio')->nullOnDelete();
            $table->foreignId('cliente_sucursal_id')->nullable()->constrained('cliente_sucursales')->nullOnDelete();
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('contacto', 120)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->decimal('peso_kg', 8, 3)->nullable();
            $table->decimal('costo', 12, 2)->default(0);
            $table->string('transportador', 120)->nullable();
            $table->string('guia_numero', 60)->nullable();
            $table->enum('estado', ['pendiente', 'en_ruta', 'entregado', 'cancelado'])->default('pendiente')->index();
            $table->date('fecha_estimada')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios');
    }
};
