<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `postulantes.modalidad_trabajo` pasa de varchar(40) a json.
 *
 * La columna nació para guardar **una** modalidad (`string('modalidad_trabajo', 40)`) y
 * más tarde 2026_07_09_000003 convirtió los datos a lista JSON, pero sin cambiar el tipo.
 * Quedó funcionando de casualidad: `["Jornada Completa"]` son 20 caracteres y cabe, pero
 * marcar dos modalidades ya se pasa de 40 y la base responde
 * `SQLSTATE[22001] value too long for type character varying(40)`.
 *
 * Con esto queda igual que las otras seis columnas de arreglo del modelo
 * (`experiencias`, `educaciones`, `idiomas`, `regiones_interes`, `industrias_interes`,
 * `habilidades`), que ya son `json`.
 *
 * El cambio de tipo se escribe distinto en cada motor —Postgres exige `USING` para
 * convertir texto a json; MySQL/MariaDB redefinen la columna con `MODIFY`—, así que se
 * ramifica por driver, igual que 2026_07_16_000005.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->normalizarValores();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE postulantes ALTER COLUMN modalidad_trabajo TYPE json USING modalidad_trabajo::json');

            return;
        }

        // MariaDB implementa `json` como longtext con un CHECK de json_valid, de ahí que
        // arriba se normalice el contenido: una fila no válida abortaría el ALTER.
        Schema::table('postulantes', function (Blueprint $tabla): void {
            $tabla->json('modalidad_trabajo')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // De vuelta a un solo valor, que es lo que cabía: se conserva la primera
            // modalidad, igual que hace el rollback de 2026_07_09_000003.
            DB::statement("ALTER TABLE postulantes ALTER COLUMN modalidad_trabajo TYPE varchar(40) USING nullif(left(coalesce(modalidad_trabajo->>0, ''), 40), '')");

            return;
        }

        // Primero se suelta la validación de JSON; si no, escribir una modalidad suelta
        // (que ya no es un arreglo) en la columna la haría fallar.
        DB::statement('ALTER TABLE postulantes MODIFY modalidad_trabajo LONGTEXT NULL');

        DB::table('postulantes')
            ->whereNotNull('modalidad_trabajo')
            ->orderBy('id')
            ->each(function (object $postulante): void {
                $lista = json_decode((string) $postulante->modalidad_trabajo, true);
                $primera = is_array($lista)
                    ? (string) ($lista[0] ?? '')
                    : (string) $postulante->modalidad_trabajo;

                DB::table('postulantes')->where('id', $postulante->id)->update([
                    'modalidad_trabajo' => $primera === '' ? null : mb_substr($primera, 0, 40),
                ]);
            });

        DB::statement('ALTER TABLE postulantes MODIFY modalidad_trabajo VARCHAR(40) NULL');
    }

    /**
     * Deja toda la columna con JSON válido antes de convertirla.
     *
     * Cubre las cadenas vacías y cualquier fila que se haya quedado con una modalidad
     * suelta (anterior a 2026_07_09_000003): se envuelve en un arreglo de un elemento,
     * que es exactamente lo que hizo aquella migración.
     */
    private function normalizarValores(): void
    {
        DB::table('postulantes')
            ->whereNotNull('modalidad_trabajo')
            ->orderBy('id')
            ->each(function (object $postulante): void {
                $valor = (string) $postulante->modalidad_trabajo;

                if (is_array(json_decode($valor, true))) {
                    return;
                }

                DB::table('postulantes')->where('id', $postulante->id)->update([
                    'modalidad_trabajo' => $valor === '' ? null : json_encode([$valor], JSON_UNESCAPED_UNICODE),
                ]);
            });
    }
};
