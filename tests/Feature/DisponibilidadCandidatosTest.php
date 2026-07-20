<?php

use App\Models\Postulante;
use App\Models\User;
use App\Services\DisponibilidadCandidatos;
use App\Services\MatchingService;
use Illuminate\Support\Facades\DB;

/** Cuenta las lecturas de `postulantes` disparadas durante el callback. */
function agregacionesDurante(Closure $callback): int
{
    $total = 0;

    DB::listen(function ($consulta) use (&$total): void {
        if (str_contains($consulta->sql, 'from "postulantes"')) {
            $total++;
        }
    });

    $callback();

    return $total;
}

function postulanteVisible(array $atributos): Postulante
{
    return Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        ...$atributos,
    ]);
}

function conteos(string $campo, array $criterios = []): array
{
    return app(DisponibilidadCandidatos::class)->conteos($campo, $criterios);
}

/** El servicio devuelve las claves normalizadas; en la vista se consultan con clave(). */
function conteo(string $campo, string $opcion, array $criterios = []): int
{
    return conteos($campo, $criterios)[DisponibilidadCandidatos::clave($opcion)] ?? 0;
}

test('sin criterios cuenta todas las fichas visibles por valor', function () {
    postulanteVisible(['carrera' => 'Ingeniería Comercial']);
    postulanteVisible(['carrera' => 'Ingeniería Comercial']);
    postulanteVisible(['carrera' => 'Periodismo']);
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => false, 'carrera' => 'Ingeniería Comercial',
    ]);

    expect(conteo('carrera', 'Ingeniería Comercial'))->toBe(2)
        ->and(conteo('carrera', 'Periodismo'))->toBe(1);
});

test('el conteo descuenta los criterios de OTROS campos', function () {
    postulanteVisible(['carrera' => 'Ingeniería Comercial', 'regiones_interes' => ['Biobío']]);
    postulanteVisible(['carrera' => 'Ingeniería Comercial', 'regiones_interes' => ['Valparaíso']]);
    postulanteVisible(['carrera' => 'Periodismo', 'regiones_interes' => ['Biobío']]);

    // Sin filtros: las dos fichas de Ingeniería Comercial.
    expect(conteo('carrera', 'Ingeniería Comercial'))->toBe(2);

    // Con región = Biobío, solo una de ellas sigue en juego.
    expect(conteo('carrera', 'Ingeniería Comercial', ['ciudad' => ['Biobío']]))->toBe(1)
        ->and(conteo('carrera', 'Periodismo', ['ciudad' => ['Biobío']]))->toBe(1);
});

test('el conteo de un campo ignora los valores ya elegidos en ese MISMO campo', function () {
    postulanteVisible(['regiones_interes' => ['Biobío']]);
    postulanteVisible(['regiones_interes' => ['Valparaíso']]);

    // Con "Biobío" elegido, "Valparaíso" debe decir cuántas fichas sumaría (OR), no cero.
    expect(conteo('ciudad', 'Valparaíso', ['ciudad' => ['Biobío']]))->toBe(1)
        // Y el propio "Biobío" mantiene su número: los valores del campo no se descuentan entre sí.
        ->and(conteo('ciudad', 'Biobío', ['ciudad' => ['Biobío']]))->toBe(1);
});

test('una ficha que incumple dos criterios no suma a ninguna faceta', function () {
    // Falla región y carrera a la vez: agregar una sola opción no la rescata.
    postulanteVisible(['carrera' => 'Periodismo', 'regiones_interes' => ['Maule'], 'genero' => 'Femenino']);

    $criterios = ['ciudad' => ['Biobío'], 'carrera' => ['Ingeniería Comercial']];

    expect(conteo('genero', 'Femenino', $criterios))->toBe(0);
});

test('una ficha que incumple solo ese campo sí suma a su propia faceta', function () {
    postulanteVisible(['carrera' => 'Periodismo', 'regiones_interes' => ['Biobío']]);

    // Cumple la región pedida y solo falla la carrera: elegir "Periodismo" la traería.
    expect(conteo('carrera', 'Periodismo', ['ciudad' => ['Biobío'], 'carrera' => ['Ingeniería Comercial']]))->toBe(1);
});

test('los criterios que no son combobox también acotan el conteo', function () {
    postulanteVisible(['carrera' => 'Ingeniería Comercial', 'anios_experiencia' => 25]);
    postulanteVisible(['carrera' => 'Ingeniería Comercial', 'anios_experiencia' => 5]);

    expect(conteo('carrera', 'Ingeniería Comercial'))->toBe(2)
        ->and(conteo('carrera', 'Ingeniería Comercial', ['experiencia' => ['min' => 20, 'max' => null]]))->toBe(1);
});

test('"Nacional" en regiones de interés suma a todas las regiones', function () {
    postulanteVisible(['regiones_interes' => ['Nacional']]);

    // El motor de calce da por buena cualquier región chilena, así que el conteo también.
    expect(conteo('ciudad', 'Biobío'))->toBe(1)
        ->and(conteo('ciudad', 'Magallanes y de la Antártica Chilena'))->toBe(1)
        ->and(conteo('ciudad', 'Internacional'))->toBe(0);
});

test('cuenta por arrays JSON: industrias y habilidades', function () {
    postulanteVisible(['industrias_interes' => ['Minería'], 'habilidades' => ['Python', 'Liderazgo']]);
    postulanteVisible(['industrias_interes' => ['Minería', 'Alimentos'], 'habilidades' => ['Python']]);

    expect(conteo('industria', 'Minería'))->toBe(2)
        ->and(conteo('industria', 'Alimentos'))->toBe(1)
        ->and(conteo('habilidad', 'Python'))->toBe(2)
        ->and(conteo('habilidad', 'Liderazgo'))->toBe(1);
});

test('no double-cuenta una ficha que repite el mismo valor', function () {
    postulanteVisible([
        'cargo_actual' => 'Gerente Finanza',
        'experiencias' => [
            ['cargo' => 'Gerente Finanza', 'empresa' => 'Codelco'],
            ['cargo' => 'Gerente Finanza', 'empresa' => 'BHP'],
        ],
    ]);
    postulanteVisible(['experiencias' => [['cargo' => 'Analista Finanzas', 'empresa' => 'Codelco']]]);

    expect(conteo('cargo', 'Gerente Finanza'))->toBe(1)
        ->and(conteo('cargo', 'Analista Finanzas'))->toBe(1)
        ->and(conteo('empresa', 'Codelco'))->toBe(2)
        ->and(conteo('empresa', 'BHP'))->toBe(1);
});

test('cuenta idiomas por combinación idioma y nivel', function () {
    postulanteVisible(['idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Avanzado'], ['idioma' => 'Español', 'nivel' => 'Avanzado']]]);
    postulanteVisible(['idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Avanzado']]]);
    postulanteVisible(['idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Intermedio']]]);

    expect(conteo('idioma', 'Inglés · Avanzado'))->toBe(2)
        ->and(conteo('idioma', 'Inglés · Intermedio'))->toBe(1)
        ->and(conteo('idioma', 'Español · Avanzado'))->toBe(1);
});

test('cuenta instituciones y niveles desde educaciones', function () {
    postulanteVisible([
        'universidad' => 'Universidad de Concepción',
        'educaciones' => [['institucion' => 'Universidad de Concepción', 'nivel' => 'Universitaria', 'situacion' => 'Titulado']],
    ]);
    postulanteVisible([
        'educaciones' => [['institucion' => 'Universidad de Chile', 'nivel' => 'Magíster', 'situacion' => 'Egresado']],
    ]);

    expect(conteo('institucion', 'Universidad de Concepción'))->toBe(1)
        ->and(conteo('institucion', 'Universidad de Chile'))->toBe(1)
        ->and(conteo('nivel_estudios', 'Universitaria'))->toBe(1)
        ->and(conteo('situacion_estudios', 'Egresado'))->toBe(1);
});

test('la comparación de valores ignora mayúsculas y espacios, igual que el matching', function () {
    postulanteVisible(['carrera' => '  ingeniería comercial ']);

    expect(conteo('carrera', 'Ingeniería Comercial'))->toBe(1);
});

test('un campo no soportado no consulta la base', function () {
    expect(agregacionesDurante(function (): void {
        expect(conteos('inventado'))->toBe([]);
    }))->toBe(0);
});

test('un solo recorrido resuelve todos los campos', function () {
    postulanteVisible(['carrera' => 'Periodismo', 'regiones_interes' => ['Biobío']]);

    // chunkById hace una consulta por tanda + una final vacía; lo que importa es que el
    // costo no crezca con la cantidad de campos consultados. Cada medición usa criterios
    // distintos para que ninguna salga de caché y ambas se midan en frío.
    $unCampo = agregacionesDurante(fn () => conteos('carrera', ['ciudad' => ['Biobío']]));

    $variosCampos = agregacionesDurante(function (): void {
        $servicio = app(DisponibilidadCandidatos::class);
        foreach (['carrera', 'ciudad', 'industria', 'genero', 'idioma'] as $campo) {
            $servicio->conteos($campo, ['ciudad' => ['Valparaíso']]);
        }
    });

    expect($unCampo)->toBeGreaterThan(0)
        ->and($variosCampos)->toBe($unCampo);
});

test('la caché evita repetir el recorrido entre instancias distintas', function () {
    postulanteVisible(['carrera' => 'Periodismo']);

    $consultas = agregacionesDurante(function (): void {
        foreach (range(1, 5) as $ignorado) {
            (new DisponibilidadCandidatos(app(MatchingService::class)))->conteos('carrera');
        }
    });

    // Solo el primer recorrido toca la base; el resto sale de caché.
    expect($consultas)->toBeGreaterThan(0);

    $repetido = agregacionesDurante(function (): void {
        (new DisponibilidadCandidatos(app(MatchingService::class)))->conteos('carrera');
    });

    expect($repetido)->toBe(0);
});

test('cada combinación de criterios se cachea por separado', function () {
    postulanteVisible(['carrera' => 'Periodismo', 'regiones_interes' => ['Biobío']]);

    conteos('carrera');

    // Criterios distintos ⇒ no puede reusar la entrada anterior.
    expect(agregacionesDurante(fn () => conteos('carrera', ['ciudad' => ['Biobío']])))->toBeGreaterThan(0);

    // La misma combinación ⇒ caché.
    expect(agregacionesDurante(fn () => conteos('carrera', ['ciudad' => ['Biobío']])))->toBe(0);
});

test('criterios equivalentes comparten entrada de caché', function () {
    postulanteVisible(['carrera' => 'Periodismo', 'regiones_interes' => ['Biobío']]);

    conteos('carrera', ['ciudad' => ['Biobío']]);

    // Mismo criterio, distinto orden y con vacíos de más: debe salir de caché igual.
    expect(agregacionesDurante(fn () => conteos('carrera', ['cargo' => [], 'ciudad' => ['Biobío'], 'genero' => []])))->toBe(0);
});

test('guardar, ocultar o borrar una ficha invalida los conteos cacheados', function () {
    $postulante = postulanteVisible(['carrera' => 'Periodismo']);

    expect(conteo('carrera', 'Periodismo'))->toBe(1);

    postulanteVisible(['carrera' => 'Periodismo']);
    expect(conteo('carrera', 'Periodismo'))->toBe(2);

    $postulante->update(['visible' => false]);
    expect(conteo('carrera', 'Periodismo'))->toBe(1);

    $postulante->update(['visible' => true]);
    expect(conteo('carrera', 'Periodismo'))->toBe(2);

    $postulante->delete();
    expect(conteo('carrera', 'Periodismo'))->toBe(1);
});
