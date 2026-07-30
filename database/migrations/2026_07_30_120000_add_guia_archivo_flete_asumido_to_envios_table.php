<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envios', function (Blueprint $table) {
            $table->string('guia_archivo', 255)->nullable()->after('guia_numero');
            $table->boolean('flete_asumido_sweetgo')->default(false)->after('costo');
        });
    }

    public function down(): void
    {
        Schema::table('envios', function (Blueprint $table) {
            $table->dropColumn(['guia_archivo', 'flete_asumido_sweetgo']);
        });
    }
};
