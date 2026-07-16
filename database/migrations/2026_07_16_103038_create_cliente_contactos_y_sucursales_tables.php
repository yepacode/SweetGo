<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_telefonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('etiqueta', 40)->nullable(); // "Principal", "WhatsApp", "Oficina"...
            $table->string('numero', 50);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('cliente_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('etiqueta', 40)->nullable(); // "Contacto", "Facturación"...
            $table->string('email', 255);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('cliente_sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre', 120);              // "Sede Norte", "Salón Chapinero"...
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('contacto', 120)->nullable(); // encargado
            $table->text('notas')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_sucursales');
        Schema::dropIfExists('cliente_emails');
        Schema::dropIfExists('cliente_telefonos');
    }
};
