<?php

use App\Models\Postulante;
use App\Models\User;
use App\Support\SecuenciasPostgres;
use Database\Seeders\PostulanteSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Deja la secuencia como en una base recién migrada.
 *
 * Hace falta fijarla a mano porque las secuencias de Postgres NO son transaccionales:
 * el rollback de RefreshDatabase no las devuelve atrás, así que sin esto el test
 * dependería de cuántos postulantes hayan creado los tests anteriores.
 */
function reiniciarSecuenciaPostulantes(): void
{
    DB::statement("SELECT setval(pg_get_serial_sequence('postulantes', 'id'), 1, false)");
}

/** Inserta una fila con id explícito, que es lo que hacen los seeders de demo. */
function insertarConIdExplicito(int $id): void
{
    $user = User::factory()->create(['role' => 'postulante']);

    DB::table('postulantes')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'visible' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('un id explícito deja la secuencia atrás y el siguiente registro choca', function () {
    reiniciarSecuenciaPostulantes();
    insertarConIdExplicito(1);
    insertarConIdExplicito(2);

    // Este es el error que se veía en producción al registrar un usuario nuevo.
    expect(fn () => Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('sincronizar la secuencia permite volver a insertar sin id', function () {
    reiniciarSecuenciaPostulantes();
    insertarConIdExplicito(1);
    insertarConIdExplicito(2);

    SecuenciasPostgres::sincronizar('postulantes');

    $nuevo = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
    ]);

    expect($nuevo->id)->toBe(3);
});

test('el seeder de postulantes deja la secuencia lista para registros reales', function () {
    reiniciarSecuenciaPostulantes();

    (new PostulanteSeeder)->run();

    // Tras el seeder, un registro real (sin id) tiene que entrar sin chocar.
    $nuevo = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
    ]);

    expect($nuevo->id)->toBeGreaterThan(Postulante::query()->where('id', '!=', $nuevo->id)->max('id'));
});
