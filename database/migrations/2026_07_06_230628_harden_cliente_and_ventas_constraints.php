<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unique('documento', 'clientes_documento_unique');
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
        });

        Schema::table('garantias', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('garantias', function (Blueprint $table) {
            $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('garantias', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('garantias', function (Blueprint $table) {
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_documento_unique');
        });
    }
};
