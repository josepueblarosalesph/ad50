<?php

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Cupon;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use App\Support\CatalogosProfesionales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * `modalidad_trabajo` nació como varchar(40) para una sola modalidad y después pasó a
 * guardar una lista JSON sin que nadie cambiara el tipo de la columna. Cabía por poco
 * —`["Jornada Completa"]` son 20 caracteres— y reventaba al marcar dos.
 */
it('guarda todas las modalidades de trabajo a la vez', function (): void {
    $user = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create(['user_id' => $user->id]);
    $todas = CatalogosProfesionales::modalidadesTrabajoPreferidas();

    $postulante->update(['modalidad_trabajo' => $todas]);

    expect($postulante->fresh()->modalidad_trabajo)->toBe($todas)
        ->and(count($todas))->toBeGreaterThan(1);
});

it('acepta un titular del largo máximo que permite el formulario', function (): void {
    $user = User::factory()->create(['role' => 'postulante']);
    $postulante = Postulante::query()->create(['user_id' => $user->id]);

    $postulante->update(['titular' => str_repeat('á', 100)]);

    expect(mb_strlen((string) $postulante->fresh()->titular))->toBe(100);
});

/**
 * Red de seguridad para el patrón completo: una columna que el modelo castea a arreglo
 * no puede estar guardada en un varchar, porque el JSON crece con cada elemento.
 */
it('no guarda ninguna columna de arreglo en un varchar', function (): void {
    $modelos = [
        Postulante::class,
        Empresa::class,
        Busqueda::class,
        Publicacion::class,
        BusquedaCandidato::class,
        Plan::class,
        Cupon::class,
    ];

    $sospechosas = [];

    foreach ($modelos as $clase) {
        $modelo = new $clase;

        foreach ($modelo->getCasts() as $columna => $cast) {
            if (! in_array($cast, ['array', 'json', 'collection'], true)) {
                continue;
            }

            $info = DB::selectOne(
                'select data_type from information_schema.columns where table_name = ? and column_name = ?',
                [$modelo->getTable(), $columna],
            );

            // MariaDB implementa `json` como longtext con un CHECK de json_valid, así que
            // el tipo que reporta el esquema es ese, no "json".
            if ($info !== null && ! in_array($info->data_type, ['json', 'jsonb', 'text', 'longtext'], true)) {
                $sospechosas[] = "{$modelo->getTable()}.$columna ({$info->data_type})";
            }
        }
    }

    expect($sospechosas)->toBeEmpty();
});
