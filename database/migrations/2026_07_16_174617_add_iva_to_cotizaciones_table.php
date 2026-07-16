<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->boolean('con_iva')->default(false)->after('descuento')->index();
            $table->decimal('iva_porcentaje', 5, 2)->default(19.00)->after('con_iva');
            $table->decimal('iva_monto', 12, 2)->default(0)->after('iva_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropIndex(['con_iva']);
            $table->dropColumn(['con_iva', 'iva_porcentaje', 'iva_monto']);
        });
    }
};
