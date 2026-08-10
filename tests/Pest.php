<?php

use App\Models\Empresa;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Ficha tal como queda al terminar el asistente de bienvenida sin llenar nada opcional:
 * solo lo que el asistente no deja saltar. Vale 55 % según CompletitudPerfil.
 *
 * @return array<string, mixed>
 */
function fichaMinimaDelAsistente(): array
{
    return [
        'rut' => '9.842.115-7',
        'telefono' => '+56 9 5555 1234',
        'anio_nacimiento' => 1971,
        'genero' => 'Femenino',
        'nacionalidad' => 'Chilena',
        'ciudad' => 'Biobío',
        'anios_experiencia' => 17,
        'titular' => 'Gerenta de Finanzas',
        'experiencias' => [['cargo' => 'Gerente Finanza', 'empresa' => 'Codelco']],
        'educaciones' => [['nivel' => 'Universitaria', 'institucion' => 'Universidad de Prueba']],
    ];
}

/**
 * Todo lo que el asistente permite saltar. Sumado a fichaMinimaDelAsistente() da 100 %.
 *
 * @return array<string, mixed>
 */
function fichaOpcionalCompleta(): array
{
    return [
        'resumen_profesional' => 'Experiencia liderando equipos financieros.',
        'habilidades' => ['Liderazgo'],
        'industrias_interes' => ['Banca y servicios financieros'],
        'cv_ruta' => 'cvs/prueba.pdf',
        'idiomas' => [['idioma' => 'Inglés', 'nivel' => 'Intermedio']],
        'regiones_interes' => ['Biobío'],
        'linkedin' => 'https://linkedin.com/in/prueba',
        'modalidad_trabajo' => ['Jornada Parcial'],
        'situacion_laboral' => 'Trabajando actualmente',
        'expectativa_renta' => 2500000,
    ];
}

/**
 * Deja una empresa lista para operar el panel: datos enviados + plan pagado vigente.
 * (Con el onboarding por pago, el panel exige ambos.)
 */
function hacerEmpresaOperativa(Empresa $empresa): Empresa
{
    $empresa->update([
        'datos_enviados_at' => now(),
        'estado_activacion' => 'activa',
        'plan_id' => Plan::query()->create([
            'codigo' => 'empresa_op_'.str()->random(8),
            'nombre' => 'AD+50 · Operativa',
            'audiencia' => 'empresa',
            'precio_clp' => 50000,
            'periodo' => 'mensual',
            'desbloqueos' => 10,
        ])->id,
        'plan_hasta' => now()->addMonth(),
    ]);

    return $empresa;
}
