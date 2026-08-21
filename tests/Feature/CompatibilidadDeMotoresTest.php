<?php

use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Producción corre MariaDB y las pruebas en Laravel Cloud corren PostgreSQL, así que
 * todo tiene que funcionar en ambos. Estas pruebas cubren las diferencias que ya nos
 * mordieron una vez.
 */

/**
 * `json_encode` escapa los acentos por defecto ("Inglés" → "Inglés"). Postgres
 * interpreta el JSON y encuentra la fila igual; **MariaDB compara el texto tal cual y no
 * la encuentra**. Por eso los modelos guardan con el cast `json:unicode`.
 */
it('encuentra un valor con tilde dentro de una columna JSON', function (): void {
    $publicacion = Publicacion::factory()->create(['idiomas' => ['Inglés', 'Ruso']]);

    expect(Publicacion::query()->whereJsonContains('idiomas', 'Inglés')->whereKey($publicacion->id)->exists())
        ->toBeTrue('El acento se guardó escapado: MariaDB no lo encontrará.')
        ->and(Publicacion::query()->whereJsonContains('idiomas', 'Ruso')->whereKey($publicacion->id)->exists())
        ->toBeTrue();
});

it('guarda los acentos literales, no escapados', function (): void {
    $publicacion = Publicacion::factory()->create(['idiomas' => ['Español']]);

    $crudo = (string) DB::table('publicaciones')->where('id', $publicacion->id)->value('idiomas');

    // El acento va tal cual; lo que no debe aparecer es su secuencia de escape.
    expect($crudo)->toContain('Español')->not->toContain('\\u00f1');
});

it('encuentra una habilidad con tilde en la ficha del postulante', function (): void {
    $user = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create([
        'user_id' => $user->id,
        'industrias_interes' => ['Educación', 'Minería'],
    ]);

    expect(Postulante::query()->whereJsonContains('industrias_interes', 'Educación')->exists())->toBeTrue()
        ->and(Postulante::query()->whereJsonContains('industrias_interes', 'Minería')->exists())->toBeTrue();
});
