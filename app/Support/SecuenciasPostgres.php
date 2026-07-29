<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Realinea las secuencias de PostgreSQL con el mayor id de cada tabla.
 *
 * En Postgres el id autoincremental sale de una secuencia. Insertar filas con un id
 * explícito (por ejemplo un `upsert` de seeder) NO la avanza, así que la secuencia
 * queda atrás y el siguiente INSERT sin id choca con una fila existente:
 *
 *   duplicate key value violates unique constraint "postulantes_pkey"
 *
 * Tras cualquier inserción con ids explícitos hay que llamar a sincronizar().
 */
class SecuenciasPostgres
{
    /**
     * Deja la secuencia de la tabla apuntando al id siguiente al mayor existente.
     * En otros motores no hace nada: el problema es propio de Postgres.
     */
    public static function sincronizar(string $tabla, string $columna = 'id'): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable($tabla)) {
            return;
        }

        // Devuelve null si la columna no es serial/identity (por ejemplo `job_batches`,
        // cuyo id es texto). Se consulta antes para no armar un setval inválido.
        $secuencia = DB::scalar('SELECT pg_get_serial_sequence(?, ?)', [$tabla, $columna]);

        if ($secuencia === null) {
            return;
        }

        $tablaSql = '"'.str_replace('"', '""', $tabla).'"';
        $columnaSql = '"'.str_replace('"', '""', $columna).'"';

        DB::statement(
            'SELECT setval(?, COALESCE((SELECT MAX('.$columnaSql.') FROM '.$tablaSql.'), 0) + 1, false)',
            [$secuencia],
        );
    }

    /**
     * Sincroniza todas las tablas del esquema que tengan una columna id serial.
     * Sirve para reparar una base donde ya se corrieron seeders con ids explícitos.
     */
    public static function sincronizarTodas(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (Schema::getTableListing(schema: 'public', schemaQualified: false) as $tabla) {
            if (Schema::hasColumn($tabla, 'id')) {
                self::sincronizar($tabla);
            }
        }
    }
}
