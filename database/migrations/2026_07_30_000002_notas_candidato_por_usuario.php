<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La nota pasa a ser de la persona que la escribió, no de la empresa entera.
 *
 * Antes había una sola nota por (empresa, postulante): cualquier usuario del equipo la
 * sobrescribía sin dejar rastro de quién fue. Ahora cada usuario tiene la suya y decide
 * si la comparte con su equipo o se la guarda para sí.
 *
 * Las notas existentes se atribuyen al contacto administrador de cada empresa (es el
 * único autor que se puede inferir) y quedan compartidas, que es como se comportaban.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_candidato', function (Blueprint $table): void {
            // Nullable y `nullOnDelete`: si la persona deja el equipo, la nota que ya
            // compartió no desaparece del historial del candidato.
            $table->foreignId('user_id')->nullable()->after('empresa_id')->constrained()->nullOnDelete();
            $table->string('visibilidad', 20)->default('equipo')->after('contenido');
        });

        DB::statement('UPDATE notas_candidato SET user_id = empresas.user_id FROM empresas WHERE empresas.id = notas_candidato.empresa_id');

        Schema::table('notas_candidato', function (Blueprint $table): void {
            $table->dropUnique(['empresa_id', 'postulante_id']);
            $table->unique(['empresa_id', 'postulante_id', 'user_id']);
        });
    }

    public function down(): void
    {
        // Al volver a una nota por empresa hay que quedarse con una sola: se conserva la
        // más reciente de cada (empresa, postulante) y se descartan las demás.
        DB::statement(<<<'SQL'
            DELETE FROM notas_candidato
            WHERE id NOT IN (
                SELECT DISTINCT ON (empresa_id, postulante_id) id
                FROM notas_candidato
                ORDER BY empresa_id, postulante_id, updated_at DESC, id DESC
            )
        SQL);

        Schema::table('notas_candidato', function (Blueprint $table): void {
            $table->dropUnique(['empresa_id', 'postulante_id', 'user_id']);
            $table->unique(['empresa_id', 'postulante_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('visibilidad');
        });
    }
};
