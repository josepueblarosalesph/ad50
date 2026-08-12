<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Todos los candidatos de una publicación pasan a tener estado, vengan de donde vengan.
 *
 * Hasta ahora el estado vivía solo en `postulaciones`: quien la empresa agregaba a mano
 * desde Prospección no tenía ninguno y solo se le podía filtrar como "Agregado". Eso
 * impedía separar el listado en dos ejes —de dónde viene y en qué etapa está—, que es
 * como se revisa un proceso: alguien agregado también se selecciona o se descarta.
 *
 * Los agregados arrancan en `en_revision`: la empresa ya los miró y los eligió a mano,
 * así que "recibida" (que describe algo que llegó solo) no les corresponde.
 *
 * De paso, el estado `enviada` pasa a llamarse `recibida`. Se renombra la clave y no solo
 * la etiqueta para que el código no acabe diciendo "enviada" donde la interfaz dice
 * "Recibida", que es la clase de desfase que después provoca errores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publicacion_candidato', function (Blueprint $table): void {
            $table->string('estado')->default('en_revision')->after('busqueda_id')->index();
        });

        // Las filas que ya existían también arrancan en revisión.
        DB::table('publicacion_candidato')->update(['estado' => 'en_revision']);

        DB::table('postulaciones')->where('estado', 'enviada')->update(['estado' => 'recibida']);

        DB::statement("ALTER TABLE postulaciones ALTER COLUMN estado SET DEFAULT 'recibida'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE postulaciones ALTER COLUMN estado SET DEFAULT 'enviada'");

        DB::table('postulaciones')->where('estado', 'recibida')->update(['estado' => 'enviada']);

        Schema::table('publicacion_candidato', function (Blueprint $table): void {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
        });
    }
};
