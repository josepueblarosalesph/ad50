<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('busquedas', function (Blueprint $t) {
            // Quién creó la búsqueda. La cuenta es de la empresa y varias personas del
            // equipo trabajan sobre ella, así que el listado necesita el autor.
            // `nullOnDelete`: si esa persona sale del equipo, la búsqueda sigue viva.
            $t->foreignId('user_id')->nullable()->after('empresa_id')->constrained()->nullOnDelete();
        });

        // Las búsquedas anteriores a esta columna se atribuyen al contacto administrador
        // de su empresa: es el único autor que consta para ellas.
        DB::table('busquedas')->update([
            'user_id' => DB::raw('(select empresas.user_id from empresas where empresas.id = busquedas.empresa_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('busquedas', function (Blueprint $t) {
            $t->dropConstrainedForeignId('user_id');
        });
    }
};
