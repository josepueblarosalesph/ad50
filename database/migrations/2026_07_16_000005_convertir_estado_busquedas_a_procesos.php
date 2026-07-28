<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // El enum de Postgres se implementa con un CHECK; lo quitamos y ampliamos la columna.
            DB::statement('ALTER TABLE busquedas ALTER COLUMN estado DROP DEFAULT');
            DB::statement('ALTER TABLE busquedas DROP CONSTRAINT IF EXISTS busquedas_estado_check');
            DB::statement('ALTER TABLE busquedas ALTER COLUMN estado TYPE varchar(40)');
            DB::statement("ALTER TABLE busquedas ALTER COLUMN estado SET DEFAULT 'long_list'");
        } else {
            // MySQL/MariaDB guardan el enum como tipo nativo: basta con redefinir la columna.
            Schema::table('busquedas', function (Blueprint $t) {
                $t->string('estado', 40)->default('long_list')->change();
            });
        }

        // Mapear los estados antiguos a las nuevas etapas del proceso.
        DB::table('busquedas')->where('estado', 'activa')->update(['estado' => 'long_list']);
        DB::table('busquedas')->where('estado', 'pausada')->update(['estado' => 'pausado']);
        DB::table('busquedas')->where('estado', 'cerrada')->update(['estado' => 'cerrado']);
    }

    public function down(): void
    {
        DB::table('busquedas')->whereIn('estado', ['long_list', 'short_list', 'entrevistas'])->update(['estado' => 'activa']);
        DB::table('busquedas')->whereIn('estado', ['cancelado_cliente', 'cancelado', 'cerrado'])->update(['estado' => 'cerrada']);
        DB::table('busquedas')->where('estado', 'pausado')->update(['estado' => 'pausada']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE busquedas ALTER COLUMN estado DROP DEFAULT');
            DB::statement("ALTER TABLE busquedas ADD CONSTRAINT busquedas_estado_check CHECK (estado::text = ANY (ARRAY['activa'::text, 'pausada'::text, 'cerrada'::text]))");
            DB::statement("ALTER TABLE busquedas ALTER COLUMN estado SET DEFAULT 'activa'");

            return;
        }

        Schema::table('busquedas', function (Blueprint $t) {
            $t->enum('estado', ['activa', 'pausada', 'cerrada'])->default('activa')->change();
        });
    }
};
