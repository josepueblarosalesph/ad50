<?php

use App\Support\SecuenciasPostgres;
use Illuminate\Database\Migrations\Migration;

/**
 * Repara las secuencias de PostgreSQL que quedaron atrás.
 *
 * Los seeders de demo (PostulanteSeeder, EmpresaSeeder) insertan con id explícito, y
 * en Postgres eso no avanza la secuencia. En una base donde se corrieron, el primer
 * registro real falla con:
 *
 *   duplicate key value violates unique constraint "postulantes_pkey"
 *
 * Se recorren todas las tablas porque el desfase puede haber quedado en cualquiera
 * donde se hayan insertado ids a mano. Es idempotente: si la secuencia ya está bien,
 * queda igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        SecuenciasPostgres::sincronizarTodas();
    }

    public function down(): void
    {
        // Realinear secuencias no tiene reverso: el estado correcto es el sincronizado.
    }
};
