<?php

use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\PostulantesPruebaSeeder;

test('el seeder de prueba crea 300 postulantes marcados sin tocar los existentes', function () {
    // Un postulante "real" previo que NO debe borrarse.
    $real = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante', 'email' => 'real@ejemplo.cl'])->id,
        'visible' => true, 'carrera' => 'Periodismo',
    ]);

    $this->seed(PostulantesPruebaSeeder::class);

    expect(User::query()->where('email', 'like', '%@ad50pruebas.test')->count())->toBe(300)
        ->and(Postulante::query()->whereHas('user', fn ($q) => $q->where('email', 'like', '%@ad50pruebas.test'))->count())->toBe(300)
        ->and(Postulante::query()->whereKey($real->id)->exists())->toBeTrue();

    // Idempotente: re-ejecutar mantiene 300 (no acumula) y sigue sin tocar el real.
    $this->seed(PostulantesPruebaSeeder::class);

    expect(User::query()->where('email', 'like', '%@ad50pruebas.test')->count())->toBe(300)
        ->and(Postulante::query()->whereKey($real->id)->exists())->toBeTrue();
});

test('los postulantes de prueba tienen datos variados', function () {
    $this->seed(PostulantesPruebaSeeder::class);

    $muestra = Postulante::query()->whereHas('user', fn ($q) => $q->where('email', 'like', '%@ad50pruebas.test'))->get();

    expect($muestra->pluck('carrera')->unique()->count())->toBeGreaterThan(20)
        ->and($muestra->pluck('ciudad')->unique()->count())->toBeGreaterThan(5)
        ->and($muestra->every(fn (Postulante $p) => $p->visible && filled($p->carrera) && filled($p->idiomas)))->toBeTrue();
});
