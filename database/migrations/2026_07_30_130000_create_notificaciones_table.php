<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('para_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 40)->index(); // ej: cotizacion_editada
            $table->string('titulo', 150);
            $table->text('mensaje');
            $table->string('url', 500)->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();

            $table->index(['para_user_id', 'leida_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
