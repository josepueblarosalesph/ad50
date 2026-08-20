<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `postulantes.modalidad_trabajo` pasa de varchar(40) a json.
 *
 * La columna nació para guardar **una** modalidad (`string('modalidad_trabajo', 40)`) y
 * más tarde 2026_07_09_000003 convirtió los datos a lista JSON, pero sin cambiar el tipo.
 * Quedó funcionando de casualidad: `["Jornada Completa"]` son 20 caracteres y cabe, pero
 * marcar dos modalidades ya se pasa de 40 y Postgres responde
 * `SQLSTATE[22001] value too long for type character varying(40)`.
 *
 * Con esto queda igual que las otras seis columnas de arreglo del modelo
 * (`experiencias`, `educaciones`, `idiomas`, `regiones_interes`, `industrias_interes`,
 * `habilidades`), que ya son `json`, y que es como CLAUDE.md la describe.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Todos los valores existentes ya son JSON válido, porque los escribe el cast
        // `array` del modelo; el nullif cubre las cadenas vacías por si quedara alguna.
        DB::statement("ALTER TABLE postulantes ALTER COLUMN modalidad_trabajo TYPE json USING nullif(modalidad_trabajo, '')::json");
    }

    public function down(): void
    {
        // De vuelta a un solo valor, que es lo que cabía: se conserva la primera
        // modalidad, igual que hace el rollback de 2026_07_09_000003.
        DB::statement("ALTER TABLE postulantes ALTER COLUMN modalidad_trabajo TYPE varchar(40) USING nullif(left(coalesce(modalidad_trabajo->>0, ''), 40), '')");
    }
};
