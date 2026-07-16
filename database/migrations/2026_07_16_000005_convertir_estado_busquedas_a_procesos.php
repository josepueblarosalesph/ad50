<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // El enum de Postgres se implementa con un CHECK; lo quitamos y ampliamos la columna.
        DB::statement('ALTER TABLE busquedas ALTER COLUMN estado DROP DEFAULT');
        DB::statement('ALTER TABLE busquedas DROP CONSTRAINT IF EXISTS busquedas_estado_check');
        DB::statement('ALTER TABLE busquedas ALTER COLUMN estado TYPE varchar(40)');

        // Mapear los estados antiguos a las nuevas etapas del proceso.
        DB::table('busquedas')->where('estado', 'activa')->update(['estado' => 'long_list']);
        DB::table('busquedas')->where('estado', 'pausada')->update(['estado' => 'pausado']);
        DB::table('busquedas')->where('estado', 'cerrada')->update(['estado' => 'cerrado']);

        DB::statement("ALTER TABLE busquedas ALTER COLUMN estado SET DEFAULT 'long_list'");
    }

    public function down(): void
    {
        DB::table('busquedas')->whereIn('estado', ['long_list', 'short_list', 'entrevistas'])->update(['estado' => 'activa']);
        DB::table('busquedas')->whereIn('estado', ['cancelado_cliente', 'cancelado', 'cerrado'])->update(['estado' => 'cerrada']);
        DB::table('busquedas')->where('estado', 'pausado')->update(['estado' => 'pausada']);

        DB::statement('ALTER TABLE busquedas ALTER COLUMN estado DROP DEFAULT');
        DB::statement("ALTER TABLE busquedas ADD CONSTRAINT busquedas_estado_check CHECK (estado::text = ANY (ARRAY['activa'::text, 'pausada'::text, 'cerrada'::text]))");
        DB::statement("ALTER TABLE busquedas ALTER COLUMN estado SET DEFAULT 'activa'");
    }
};
