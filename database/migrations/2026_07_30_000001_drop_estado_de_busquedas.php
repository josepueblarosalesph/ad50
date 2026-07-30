<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La etapa del proceso de selección (Long List, Short List, Entrevistas…) pasa a la
 * publicación, que es donde la empresa gestiona el proceso. La búsqueda queda como lo
 * que realmente es: una configuración de filtros guardada, siempre vigente para el
 * matching. Con eso, `busquedas.estado` deja de tener sentido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('busquedas', function (Blueprint $t) {
            $t->dropColumn('estado');
        });
    }

    public function down(): void
    {
        Schema::table('busquedas', function (Blueprint $t) {
            $t->string('estado', 40)->default('long_list');
        });

        // Sin dato que restaurar: toda búsqueda existente vuelve a la etapa inicial.
        DB::table('busquedas')->update(['estado' => 'long_list']);
    }
};
