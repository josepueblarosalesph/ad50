<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('postulantes')->where('genero', 'Mujer')->update(['genero' => 'Femenino']);
        DB::table('postulantes')->where('genero', 'Hombre')->update(['genero' => 'Masculino']);
        DB::table('postulantes')
            ->whereNotNull('genero')
            ->whereNotIn('genero', ['Femenino', 'Masculino'])
            ->update(['genero' => 'Prefiero no Informar']);
    }

    public function down(): void
    {
        DB::table('postulantes')->where('genero', 'Femenino')->update(['genero' => 'Mujer']);
        DB::table('postulantes')->where('genero', 'Masculino')->update(['genero' => 'Hombre']);
        DB::table('postulantes')->where('genero', 'Prefiero no Informar')->update(['genero' => 'Prefiero no informar']);
    }
};
