<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->comment('Quien registró el pago');
            $table->enum('metodo', ['efectivo', 'transferencia', 'tarjeta', 'credito'])->index();
            $table->decimal('monto', 12, 2);
            $table->string('referencia', 100)->nullable();
            $table->string('comprobante')->nullable();
            $table->text('notas')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente')->index();
            $table->foreignId('aprobado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->string('rechazo_motivo', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
