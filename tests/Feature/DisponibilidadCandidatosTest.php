<?php

use App\Models\Postulante;
use App\Models\User;
use App\Services\DisponibilidadCandidatos;

function postulanteVisible(array $atributos): Postulante
{
    return Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        ...$atributos,
    ]);
}

test('cuenta postulantes por carrera, ignorando los no visibles', function () {
    postulanteVisible(['carrera' => 'Ingeniería Comercial']);
    postulanteVisible(['carrera' => 'Ingeniería Comercial']);
    postulanteVisible(['carrera' => 'Periodismo']);
    Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => false, 'carrera' => 'Ingeniería Comercial',
    ]);

    $conteos = app(DisponibilidadCandidatos::class)->conteos('carrera');

    expect($conteos['Ingeniería Comercial'] ?? 0)->toBe(2)
        ->and($conteos['Periodismo'] ?? 0)->toBe(1);
});

test('cuenta por arrays JSON: regiones, industrias y habilidades', function () {
    postulanteVisible(['regiones_interes' => ['Biobío', 'Nacional'], 'industrias_interes' => ['Minería'], 'habilidades' => ['Python', 'Liderazgo']]);
    postulanteVisible(['regiones_interes' => ['Biobío'], 'industrias_interes' => ['Minería', 'Alimentos'], 'habilidades' => ['Python']]);

    $servicio = app(DisponibilidadCandidatos::class);

    expect($servicio->conteos('ciudad')['Biobío'] ?? 0)->toBe(2)
        ->and($servicio->conteos('ciudad')['Nacional'] ?? 0)->toBe(1)
        ->and($servicio->conteos('industria')['Minería'] ?? 0)->toBe(2)
        ->and($servicio->conteos('industria')['Alimentos'] ?? 0)->toBe(1)
        ->and($servicio->conteos('habilidad')['Python'] ?? 0)->toBe(2)
        ->and($servicio->conteos('habilidad')['Liderazgo'] ?? 0)->toBe(1);
});

test('cuenta cargos y empresas desde experiencias sin doble-contar', function () {
    postulanteVisible([
        'cargo_actual' => 'Gerente Finanza',
        'experiencias' => [
            ['cargo' => 'Gerente Finanza', 'empresa' => 'Codelco'],
            ['cargo' => 'Gerente Finanza', 'empresa' => 'BHP'],
        ],
    ]);
    postulanteVisible([
        'experiencias' => [['cargo' => 'Analista Finanzas', 'empresa' => 'Codelco']],
    ]);

    $servicio = app(DisponibilidadCandidatos::class);

    // El primer postulante tiene "Gerente Finanza" en 2 experiencias + cargo_actual: cuenta 1.
    expect($servicio->conteos('cargo')['Gerente Finanza'] ?? 0)->toBe(1)
        ->and($servicio->conteos('cargo')['Analista Finanzas'] ?? 0)->toBe(1)
        ->and($servicio->conteos('empresa')['Codelco'] ?? 0)->toBe(2)
        ->and($servicio->conteos('empresa')['BHP'] ?? 0)->toBe(1);
});

test('cuenta idiomas por combinación idioma y nivel', function () {
    postulanteVisible(['idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Avanzado'], ['idioma' => 'Español', 'nivel' => 'Avanzado']]]);
    postulanteVisible(['idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Avanzado']]]);
    postulanteVisible(['idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Intermedio']]]);

    $conteos = app(DisponibilidadCandidatos::class)->conteos('idioma');

    expect($conteos['Inglés · Avanzado'] ?? 0)->toBe(2)
        ->and($conteos['Inglés · Intermedio'] ?? 0)->toBe(1)
        ->and($conteos['Español · Avanzado'] ?? 0)->toBe(1);
});

test('cuenta instituciones desde educaciones y universidad', function () {
    postulanteVisible([
        'universidad' => 'Universidad de Concepción',
        'educaciones' => [['institucion' => 'Universidad de Concepción']],
    ]);
    postulanteVisible([
        'educaciones' => [['institucion' => 'Universidad de Chile']],
    ]);

    $conteos = app(DisponibilidadCandidatos::class)->conteos('institucion');

    expect($conteos['Universidad de Concepción'] ?? 0)->toBe(1)
        ->and($conteos['Universidad de Chile'] ?? 0)->toBe(1);
});
