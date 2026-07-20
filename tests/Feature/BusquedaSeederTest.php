<?php

use App\Models\Busqueda;
use App\Models\Postulante;
use App\Support\CatalogosProfesionales;
use Database\Seeders\BusquedaSeeder;
use Database\Seeders\EmpresaSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\PostulanteSeeder;

beforeEach(function (): void {
    $this->seed(PlanSeeder::class);
    $this->seed(PostulanteSeeder::class);
    $this->seed(EmpresaSeeder::class);
    $this->seed(BusquedaSeeder::class);
});

it('crea procesos y todos quedan con al menos un candidato que cumple', function (): void {
    $busquedas = Busqueda::query()->with('candidatos')->get();

    expect($busquedas)->not->toBeEmpty();

    foreach ($busquedas as $busqueda) {
        expect($busqueda->candidatos->where('estado_match', 'cumple'))
            ->not->toBeEmpty("El proceso «{$busqueda->titulo}» quedó sin candidatos.");
    }
});

it('guarda los criterios con las claves de la forma actual de buscar', function (): void {
    $vigentes = [
        'cargo', 'carrera', 'especialidad', 'industria', 'ciudad', 'habilidad',
        'situacion_laboral', 'genero', 'nivel_estudios', 'situacion_estudios',
        'idioma', 'actividad_economica', 'renta_max', 'institucion', 'empresa',
        'experiencia', 'palabra_clave', 'edad',
    ];

    foreach (Busqueda::all() as $busqueda) {
        expect(array_keys($busqueda->criterios ?? []))->each->toBeIn($vigentes);
        expect($busqueda->criterios ?? [])->not->toHaveKey('min_anios');
    }
});

it('usa valores que los catálogos aceptan como criterio', function (): void {
    foreach (Busqueda::all() as $busqueda) {
        $criterios = $busqueda->criterios ?? [];

        expect($criterios['industria'] ?? [])->each->toBeIn(CatalogosProfesionales::industrias());
        expect($criterios['ciudad'] ?? [])->each->toBeIn(CatalogosProfesionales::regionesInteres());
        expect($criterios['genero'] ?? [])->each->toBeIn(CatalogosProfesionales::generos());
        expect($criterios['nivel_estudios'] ?? [])->each->toBeIn(CatalogosProfesionales::nivelesEstudio());
        expect($criterios['situacion_estudios'] ?? [])->each->toBeIn(CatalogosProfesionales::situacionesEstudio());
        expect($criterios['idioma'] ?? [])->each->toBeIn(CatalogosProfesionales::idiomasConNivel());
    }
});

it('deja fuera las búsquedas previas con criterios antiguos', function (): void {
    $vieja = Busqueda::query()->first()->replicate();
    $vieja->titulo = 'Proceso legacy';
    $vieja->criterios = ['cargo' => ['Finanzas'], 'min_anios' => 15];
    $vieja->save();

    $this->seed(BusquedaSeeder::class);

    expect(Busqueda::query()->where('titulo', 'Proceso legacy')->exists())->toBeFalse();
});

it('no crea procesos si no hay postulantes visibles', function (): void {
    Postulante::query()->update(['visible' => false]);

    $this->seed(BusquedaSeeder::class);

    expect(Busqueda::count())->toBeGreaterThan(0);
});
