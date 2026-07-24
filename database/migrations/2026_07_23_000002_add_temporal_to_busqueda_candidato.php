<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $t) {
            // Marca las coincidencias materializadas solo para la previsualización de un
            // borrador de filtros sin guardar. No cuentan como candidatos confirmados del
            // proceso hasta que la empresa guarda la búsqueda.
            $t->boolean('temporal')->default(false)->after('estado_match');
        });
    }

    public function down(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $t) {
            $t->dropColumn('temporal');
        });
    }
};
