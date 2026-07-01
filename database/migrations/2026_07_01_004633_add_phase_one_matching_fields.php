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
        Schema::table('postulantes', function (Blueprint $table) {
            $table->json('experiencias')->nullable()->after('resumen_profesional');
        });

        Schema::table('busqueda_candidato', function (Blueprint $table) {
            $table->json('criterios_detalle')->nullable()->after('criterios_totales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $table) {
            $table->dropColumn('criterios_detalle');
        });

        Schema::table('postulantes', function (Blueprint $table) {
            $table->dropColumn('experiencias');
        });
    }
};
