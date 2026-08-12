<?php

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Favorito;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Carbon\CarbonInterface;
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
        'educaciones' => [['nivel' => 'Título Profesional', 'institucion' => 'Universidad de Prueba']],
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
 * Empresa operativa con dos búsquedas y una publicación.
 *
 * @return array{0: User, 1: Empresa, 2: Busqueda, 3: Busqueda, 4: Publicacion}
 */
function empresaConFavoritos(): array
{
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Favoritos '.fake()->unique()->numerify('####'),
        'estado_activacion' => 'activa',
    ]);
    hacerEmpresaOperativa($empresa);

    $liderazgo = $empresa->busquedas()->create(['titulo' => 'Liderazgo', 'criterios' => []]);
    $planta = $empresa->busquedas()->create(['titulo' => 'Planta Sur', 'criterios' => []]);
    $publicacion = Publicacion::factory()->create(['empresa_id' => $empresa->id, 'cargo' => 'Jefe de Planta']);

    return [$user->fresh(), $empresa->fresh(), $liderazgo, $planta, $publicacion];
}

/**
 * Crea un candidato que calza con la búsqueda y, si corresponde, lo guarda en los
 * favoritos de la empresa (que ya no dependen de la búsqueda: solo registra el origen).
 */
function candidatoEnBusqueda(Busqueda $busqueda, bool $favorito = true, string $cargo = 'Gerente'): BusquedaCandidato
{
    $postulante = Postulante::query()->create([
        'user_id' => User::factory()->create(['role' => 'postulante'])->id,
        'visible' => true,
        'cargo_actual' => $cargo,
    ]);

    $match = $busqueda->candidatos()->create([
        'postulante_id' => $postulante->id,
        'criterios_cumplidos' => 1,
        'criterios_totales' => 1,
        'estado_match' => 'cumple',
    ]);

    if ($favorito) {
        Favorito::query()->create([
            'empresa_id' => $busqueda->empresa_id,
            'postulante_id' => $postulante->id,
            'busqueda_id' => $busqueda->id,
        ]);
    }

    return $match;
}

/**
 * Deja a la empresa con exactamente este plan y sus cupos.
 *
 * Fija los cupos en lugar de acumularlos (que es lo que hace `activarPlan()` al comprar):
 * estos tests parten de un plan concreto y afirman números exactos, así que sumar lo que
 * la empresa ya tuviera los volvería frágiles.
 */
function darPlanA(Empresa $empresa, Plan $plan, ?CarbonInterface $hasta = null): Empresa
{
    $empresa->update([
        'plan_id' => $plan->id,
        'plan_hasta' => $hasta ?? now()->addMonth(),
        'desbloqueos_cupo' => (int) ($plan->desbloqueos ?? 0),
        'publicaciones_cupo' => $plan->publicaciones,
    ]);

    return $empresa->fresh();
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
    ]);

    // activarPlan y no un update suelto: el cupo de desbloqueos y publicaciones se
    // acumula en la empresa, así que asignar el plan sin concederlos la dejaría con plan
    // vigente y cero cupos.
    $empresa->activarPlan(Plan::query()->create([
        'codigo' => 'empresa_op_'.str()->random(8),
        'nombre' => 'AD+50 · Operativa',
        'audiencia' => 'empresa',
        'precio_clp' => 50000,
        'periodo' => 'mensual',
        'desbloqueos' => 10,
    ]), now()->addMonth());

    return $empresa;
}
