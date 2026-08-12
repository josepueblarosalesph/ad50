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

        // Subconsulta correlacionada en vez de `UPDATE ... FROM`: esa forma es de
        // PostgreSQL y MariaDB/MySQL la rechazan, así que un despliegue nuevo sobre
        // MySQL moría aquí. Esta sintaxis la entienden los dos motores.
        DB::statement(<<<'SQL'
            UPDATE notas_candidato
               SET user_id = (
                   SELECT empresas.user_id FROM empresas WHERE empresas.id = notas_candidato.empresa_id
               )
        SQL);

        // Primero se crea el índice nuevo y después se quita el viejo, no al revés:
        // MySQL/MariaDB se apoyan en el índice para sostener la clave foránea de
        // `empresa_id` y se niegan a soltarlo si es el único que lo cubre. Creando antes
        // el de tres columnas, su prefijo por la izquierda sirve de apoyo y el viejo ya
        // se puede quitar. En PostgreSQL el orden da igual.
        Schema::table('notas_candidato', function (Blueprint $table): void {
            $table->unique(['empresa_id', 'postulante_id', 'user_id']);
        });

        Schema::table('notas_candidato', function (Blueprint $table): void {
            $table->dropUnique(['empresa_id', 'postulante_id']);
        });
    }

    public function down(): void
    {
        // Al volver a una nota por empresa hay que quedarse con una sola: se conserva la
        // más reciente de cada (empresa, postulante) y se descartan las demás.
        // `DISTINCT ON` solo existe en PostgreSQL. Con una función de ventana sale lo
        // mismo en ambos motores, y la tabla derivada evita la restricción de MySQL de
        // no poder leer de la tabla que se está borrando.
        DB::statement(<<<'SQL'
            DELETE FROM notas_candidato
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (
                        PARTITION BY empresa_id, postulante_id
                        ORDER BY updated_at DESC, id DESC
                    ) AS fila
                    FROM notas_candidato
                ) AS ultimas
                WHERE fila = 1
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
