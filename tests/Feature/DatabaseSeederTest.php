<?php

use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('database seeders create diverse and filterable demo data', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Postulante::query()->count())->toBe(18)
        ->and(Empresa::query()->count())->toBe(5)
        ->and(Busqueda::query()->count())->toBe(11)
        ->and(Postulante::query()->distinct()->count('ciudad'))->toBeGreaterThanOrEqual(12)
        ->and(Postulante::query()->distinct()->count('industria'))->toBeGreaterThanOrEqual(12)
        ->and(Postulante::query()->distinct()->count('genero'))->toBeGreaterThanOrEqual(3)
        ->and(Postulante::query()->whereNotNull('educaciones')->count())->toBe(18)
        ->and(Postulante::query()->whereNotNull('idiomas')->count())->toBe(18)
        ->and(Postulante::query()->whereNotNull('experiencias')->count())->toBe(18)
        ->and(Busqueda::query()->has('candidatos')->count())->toBe(11)
        ->and(User::query()->where('email', 'maria@adconsulting.cl')->firstOrFail()->postulante?->completitud)->toBe(100);
});

test('database seeders are idempotent', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Postulante::query()->count())->toBe(18)
        ->and(Empresa::query()->count())->toBe(5)
        ->and(Busqueda::query()->count())->toBe(11);
});
