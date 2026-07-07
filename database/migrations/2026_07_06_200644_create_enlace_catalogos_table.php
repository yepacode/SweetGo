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
        Schema::create('enlace_catalogos', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique();
            $table->string('titulo')->nullable(); // p.ej. "Público", "Mayoristas"
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('visitas')->default(0);
            $table->timestamp('ultima_visita')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enlace_catalogos');
    }
};
