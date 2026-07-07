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
        Schema::create('garantia_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garantia_id')->constrained('garantias')->cascadeOnDelete();
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->boolean('es_imagen')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garantia_documentos');
    }
};
