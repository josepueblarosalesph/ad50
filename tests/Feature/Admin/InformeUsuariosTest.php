<?php

use App\Livewire\Admin\Usuarios;
use App\Models\User;
use App\Support\InformeDeUsuarios;
use Carbon\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** El binario de la descarga que Livewire dejó en los efectos del componente. */
function pdfDelInforme(Testable $componente): string
{
    return (string) base64_decode((string) data_get($componente->effects, 'download.content'), true);
}

test('el informe agrupa las cuentas por tipo y cuenta las verificadas', function () {
    User::factory()->count(3)->create(['role' => 'postulante']);
    User::factory()->create(['role' => 'postulante', 'email_verified_at' => null]);
    User::factory()->create(['role' => 'empresa']);

    $informe = InformeDeUsuarios::armar();

    expect($informe['resumen']['postulante'])->toMatchArray([
        'etiqueta' => 'Postulante',
        'total' => 4,
        'verificadas' => 3,
        'sin_verificar' => 1,
    ]);

    expect($informe['resumen']['empresa']['total'])->toBe(1);
    expect($informe['total'])->toBe(5);
    expect($informe['totalVerificadas'])->toBe(4);
    expect($informe['totalSinVerificar'])->toBe(1);
    expect($informe['usuariosPorTipo']['postulante'])->toHaveCount(4);
});

test('las cuentas internas quedan fuera del informe y de sus totales', function () {
    User::factory()->count(2)->create(['role' => 'postulante']);
    User::factory()->count(3)->create(['role' => 'admin']);
    User::factory()->create(['role' => 'superadmin']);

    $informe = InformeDeUsuarios::armar();

    // El equipo interno no es gente registrada en la plataforma: ni fila ni total.
    expect($informe['resumen'])->not->toHaveKey('admin');
    expect($informe['resumen'])->not->toHaveKey('superadmin');
    expect($informe['usuariosPorTipo'])->not->toHaveKey('admin');
    expect($informe['total'])->toBe(2);
});

test('el informe lista los tipos sin ninguna cuenta en vez de omitirlos', function () {
    User::factory()->create(['role' => 'postulante']);

    $informe = InformeDeUsuarios::armar();

    // «Ninguna empresa registrada» es un dato: la fila tiene que estar, en cero.
    expect(array_keys($informe['resumen']))->toBe(InformeDeUsuarios::ROLES);
    expect($informe['resumen']['empresa']['total'])->toBe(0);
    expect($informe['usuariosPorTipo']['empresa'])->toHaveCount(0);
});

test('se puede pedir el informe de un solo tipo', function () {
    User::factory()->count(2)->create(['role' => 'postulante']);
    User::factory()->count(5)->create(['role' => 'empresa']);

    $soloPostulantes = InformeDeUsuarios::armar('postulante');

    expect(array_keys($soloPostulantes['resumen']))->toBe(['postulante']);
    expect($soloPostulantes['total'])->toBe(2);
    expect($soloPostulantes['titulo'])->toBe('Informe de postulantes registrados');

    $soloEmpresas = InformeDeUsuarios::armar('empresa');

    expect(array_keys($soloEmpresas['resumen']))->toBe(['empresa']);
    expect($soloEmpresas['total'])->toBe(5);
    expect($soloEmpresas['titulo'])->toBe('Informe de empresas registradas');
});

test('un alcance desconocido no se degrada al informe completo', function () {
    User::factory()->create(['role' => 'postulante']);

    // Incluido «admin»: pedirlo por su nombre de rol tampoco puede colarlo.
    foreach (['admin', 'superadmin', 'inventado'] as $alcance) {
        expect(fn () => InformeDeUsuarios::armar($alcance))
            ->toThrow(HttpException::class);
    }
});

test('dentro de cada tipo las cuentas van de la más reciente a la más antigua', function () {
    // Fechas fijas y lejos de los cambios de hora de Chile: una marca que caiga en la
    // hora que el país se salta al entrar el horario de verano la rechaza MariaDB.
    $base = Carbon::parse('2026-06-15 12:00:00');

    $antigua = User::factory()->create(['role' => 'empresa', 'created_at' => $base->copy()->subDays(30)]);
    $reciente = User::factory()->create(['role' => 'empresa', 'created_at' => $base]);
    $media = User::factory()->create(['role' => 'empresa', 'created_at' => $base->copy()->subDays(10)]);

    $orden = InformeDeUsuarios::armar()['usuariosPorTipo']['empresa']->pluck('id')->all();

    expect($orden)->toBe([$reciente->id, $media->id, $antigua->id]);
});

test('un superadmin descarga cada informe como PDF, con su propio nombre de archivo', function () {
    $super = User::factory()->create(['role' => 'superadmin']);
    User::factory()->count(4)->create(['role' => 'postulante']);
    User::factory()->count(2)->create(['role' => 'empresa']);

    $hoy = now()->format('Y-m-d');

    foreach ([
        'todos' => "ad50-usuarios-registrados-{$hoy}.pdf",
        'postulante' => "ad50-postulantes-{$hoy}.pdf",
        'empresa' => "ad50-empresas-{$hoy}.pdf",
    ] as $alcance => $archivo) {
        $componente = Livewire::actingAs($super)
            ->test(Usuarios::class)
            ->call('descargarInforme', $alcance)
            ->assertFileDownloaded($archivo);

        expect(data_get($componente->effects, 'download.contentType'))->toBe('application/pdf');

        // Que sea un PDF de verdad y no una página de error con la cabecera puesta.
        expect(pdfDelInforme($componente))->toStartWith('%PDF-');
    }
});

test('el informe abarca el padrón completo aunque la pantalla esté filtrada', function () {
    $super = User::factory()->create(['role' => 'superadmin']);
    User::factory()->count(12)->create(['role' => 'postulante']);
    User::factory()->count(4)->create(['role' => 'empresa']);

    $sinFiltrar = pdfDelInforme(
        Livewire::actingAs($super)->test(Usuarios::class)->call('descargarInforme')
    );

    // Un filtro que en pantalla deja una sola fila. Si el informe lo mirara, el PDF
    // saldría mucho más corto; el largo es estable porque la fecha de creación que
    // escribe dompdf ocupa siempre lo mismo.
    $filtrado = pdfDelInforme(
        Livewire::actingAs($super)
            ->test(Usuarios::class)
            ->set('rol', 'superadmin')
            ->set('verificacion', 'pendientes')
            ->set('buscar', 'nadie-con-este-nombre')
            ->call('descargarInforme')
    );

    expect(strlen($filtrado))->toBe(strlen($sinFiltrar));
});

test('solo el superadmin puede descargar el informe', function () {
    foreach (['admin', 'empresa', 'postulante'] as $rol) {
        $user = User::factory()->create(['role' => $rol]);

        // El admin ni siquiera entra a la pantalla; los otros dos tampoco.
        $this->actingAs($user)->get(route('admin.usuarios'))->assertForbidden();
    }
});
