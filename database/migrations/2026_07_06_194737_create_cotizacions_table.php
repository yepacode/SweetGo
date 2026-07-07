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
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique(); // COT-0001
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'enviada', 'aprobada', 'rechazada'])->default('borrador');
            $table->date('fecha');
            $table->date('validez')->nullable(); // válida hasta
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0); // valor absoluto en COP
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->boolean('stock_descontado')->default(false);
            $table->timestamp('aprobada_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
