<?php

use App\Livewire\Admin\Mensajes as AdminMensajes;
use App\Livewire\Ayuda;
use App\Mail\MensajeContactoRecibido;
use App\Models\Empresa;
use App\Models\MensajeContacto;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

// Nombres con sufijo -ParaAyuda: las funciones de un archivo de test son globales al
// ejecutar la suite entera, y `postulanteConFicha` ya existe en PostulacionesTest.
function postulanteParaAyuda(): User
{
    $user = User::factory()->create(['role' => 'postulante', 'name' => 'Ana Torres']);
    Postulante::query()->create([
        'user_id' => $user->id,
        ...fichaMinimaDelAsistente(),
        'onboarding_completado' => true,
        'onboarding_paso' => 6,
    ]);

    return $user->fresh();
}

function empresaParaAyuda(): User
{
    $user = User::factory()->create(['role' => 'empresa']);
    $empresa = Empresa::query()->create([
        'user_id' => $user->id,
        'razon_social' => 'Empresa Ayuda '.fake()->unique()->numerify('####'),
        'estado_activacion' => 'activa',
    ]);
    hacerEmpresaOperativa($empresa);

    return $user->fresh();
}

test('la ayuda exige sesión iniciada', function () {
    $this->get(route('ayuda'))->assertRedirect(route('login'));
});

test('el postulante ve sus preguntas y no las de empresas', function () {
    Livewire::actingAs(postulanteParaAyuda())
        ->test(Ayuda::class)
        ->assertSee('¿Cómo me encuentran las empresas?')
        ->assertSee('¿Qué pasa con mis datos personales?')   // general
        ->assertDontSee('¿Qué es un desbloqueo?');           // de empresas
});

test('la empresa ve sus preguntas y no las de postulantes', function () {
    Livewire::actingAs(empresaParaAyuda())
        ->test(Ayuda::class)
        ->assertSee('¿Qué es un desbloqueo?')
        ->assertSee('¿Qué pasa con mis datos personales?')
        ->assertDontSee('¿Cómo me encuentran las empresas?');
});

test('la respuesta solo se muestra al abrir la pregunta', function () {
    $componente = Livewire::actingAs(postulanteParaAyuda())->test(Ayuda::class);

    $componente->assertDontSee('Crear tu perfil y aparecer en las búsquedas no tiene costo.');

    // "¿Es gratis para mí?" es la quinta de la lista de postulante (índice 4).
    $componente->call('alternar', 4)
        ->assertSee('Crear tu perfil y aparecer en las búsquedas no tiene costo.')
        ->call('alternar', 4)
        ->assertDontSee('Crear tu perfil y aparecer en las búsquedas no tiene costo.');
});

test('los tres motivos están disponibles en el formulario', function () {
    Livewire::actingAs(postulanteParaAyuda())
        ->test(Ayuda::class)
        ->assertSee('Consultas sobre los servicios')
        ->assertSee('Soporte técnico')
        ->assertSee('Otras consultas')
        // El primero viene preseleccionado.
        ->assertSet('motivo', 'servicios');
});

test('enviar un mensaje lo deja en la bandeja con los datos de la cuenta', function () {
    $user = postulanteParaAyuda();

    Livewire::actingAs($user)
        ->test(Ayuda::class)
        ->set('motivo', 'soporte')
        ->set('mensaje', 'No puedo subir mi currículum, la página se queda cargando.')
        ->call('enviar')
        ->assertHasNoErrors()
        ->assertSet('mensaje', '')
        ->assertSee('Recibimos tu mensaje');

    $mensaje = MensajeContacto::query()->firstOrFail();

    expect($mensaje->user_id)->toBe($user->id)
        ->and($mensaje->motivo)->toBe('soporte')
        ->and($mensaje->nombre)->toBe('Ana Torres')
        ->and($mensaje->email)->toBe($user->email)
        ->and($mensaje->estado)->toBe('nuevo');
});

test('el mensaje se valida: motivo del catálogo y texto con contenido', function () {
    $componente = Livewire::actingAs(postulanteParaAyuda())->test(Ayuda::class);

    $componente->set('mensaje', '')->call('enviar')->assertHasErrors('mensaje');
    $componente->set('mensaje', 'ayuda')->call('enviar')->assertHasErrors('mensaje');
    $componente->set('motivo', 'inventado')
        ->set('mensaje', 'Un mensaje suficientemente largo para pasar el mínimo.')
        ->call('enviar')
        ->assertHasErrors('motivo');

    expect(MensajeContacto::query()->count())->toBe(0);
});

test('el mensaje conserva a quién responder aunque se elimine la cuenta', function () {
    $user = postulanteParaAyuda();
    $correo = $user->email;

    Livewire::actingAs($user)
        ->test(Ayuda::class)
        ->set('mensaje', 'Una consulta sobre los planes disponibles para mí.')
        ->call('enviar');

    $user->delete();

    $mensaje = MensajeContacto::query()->firstOrFail();

    expect($mensaje->user_id)->toBeNull()
        ->and($mensaje->nombre)->toBe('Ana Torres')
        ->and($mensaje->email)->toBe($correo);
});

test('la bandeja de mensajes es solo para el admin', function () {
    $this->actingAs(postulanteParaAyuda())->get(route('admin.mensajes'))->assertForbidden();
    $this->actingAs(empresaParaAyuda())->get(route('admin.mensajes'))->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.mensajes'))->assertOk();
});

test('abrir un mensaje lo marca como leído', function () {
    $mensaje = MensajeContacto::query()->create([
        'motivo' => 'soporte', 'nombre' => 'Ana', 'email' => 'ana@example.com',
        'mensaje' => 'No puedo entrar a mi cuenta.',
    ]);

    expect($mensaje->estado)->toBe('nuevo');

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(AdminMensajes::class)
        ->call('abrir', $mensaje->id)
        ->assertSet('abiertoId', $mensaje->id)
        ->assertSee('No puedo entrar a mi cuenta.');

    expect($mensaje->fresh()->estado)->toBe('leido');
});

test('marcar respondido lo saca de pendientes y se puede reabrir', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $mensaje = MensajeContacto::query()->create([
        'motivo' => 'servicios', 'nombre' => 'Ana', 'email' => 'ana@example.com',
        'mensaje' => 'Quiero saber más sobre los planes.',
    ]);

    $componente = Livewire::actingAs($admin)->test(AdminMensajes::class);

    $componente->call('marcarRespondido', $mensaje->id);
    expect($mensaje->fresh()->estado)->toBe('respondido')
        ->and($mensaje->fresh()->respondido_at)->not->toBeNull();

    // Por omisión la bandeja muestra pendientes: el respondido ya no está.
    $componente->assertViewHas('mensajes', fn ($m) => $m->total() === 0)
        ->assertViewHas('totalPendientes', 0);

    $componente->call('reabrir', $mensaje->id);
    expect($mensaje->fresh()->estado)->toBe('leido')
        ->and($mensaje->fresh()->respondido_at)->toBeNull();
});

test('la bandeja filtra por motivo y por estado', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (['servicios' => 'Consulta de servicios aquí.', 'soporte' => 'Un fallo técnico aquí.'] as $motivo => $texto) {
        MensajeContacto::query()->create([
            'motivo' => $motivo, 'nombre' => 'Ana', 'email' => 'ana@example.com', 'mensaje' => $texto,
        ]);
    }

    $componente = Livewire::actingAs($admin)->test(AdminMensajes::class);

    $componente->assertViewHas('mensajes', fn ($m) => $m->total() === 2)
        ->set('motivo', 'soporte')
        ->assertViewHas('mensajes', fn ($m) => $m->total() === 1)
        ->assertSee('Un fallo técnico aquí.')
        ->assertDontSee('Consulta de servicios aquí.');

    $componente->set('motivo', 'todos')->set('estado', 'respondido')
        ->assertViewHas('mensajes', fn ($m) => $m->total() === 0);
});

test('el menú de perfil enlaza a la ayuda', function () {
    $this->actingAs(postulanteParaAyuda())
        ->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertSee('href="'.route('ayuda').'"', false)
        ->assertSee('Ayuda y contacto');
});

test('un mensaje de soporte técnico se avisa solo a la casilla de soporte', function () {
    Mail::fake();

    // Hay administración con cuenta, y aun así el soporte no le llega: tiene casilla propia.
    User::factory()->create(['role' => 'admin', 'email' => 'admin@ad50.cl']);
    config()->set('ad50.contacto.soporte', 'contacto.ad50.portal@gmail.com');

    Livewire::actingAs(postulanteParaAyuda())
        ->test(Ayuda::class)
        ->set('motivo', 'soporte')
        ->set('mensaje', 'No puedo subir mi currículum, la pantalla se queda cargando.')
        ->call('enviar')
        ->assertHasNoErrors();

    Mail::assertSent(MensajeContactoRecibido::class, function (MensajeContactoRecibido $mail) {
        return $mail->hasTo('contacto.ad50.portal@gmail.com')
            && ! $mail->hasTo('admin@ad50.cl')
            && $mail->mensajeContacto->motivo === 'soporte';
    });

    Mail::assertSentCount(1);
});

test('los demás motivos se avisan a todas las cuentas de administración', function () {
    Mail::fake();

    User::factory()->create(['role' => 'admin', 'email' => 'admin.uno@ad50.cl']);
    User::factory()->create(['role' => 'superadmin', 'email' => 'jefe@ad50.cl']);
    // Ruido: ni postulantes ni empresas reciben el aviso.
    User::factory()->create(['role' => 'empresa', 'email' => 'ajena@empresa.cl']);
    config()->set('ad50.contacto.soporte', 'contacto.ad50.portal@gmail.com');

    foreach (['servicios', 'otras'] as $motivo) {
        Livewire::actingAs(postulanteParaAyuda())
            ->test(Ayuda::class)
            ->set('motivo', $motivo)
            ->set('mensaje', 'Quisiera saber qué incluye cada plan antes de decidirme.')
            ->call('enviar')
            ->assertHasNoErrors();
    }

    Mail::assertSent(MensajeContactoRecibido::class, function (MensajeContactoRecibido $mail) {
        return $mail->hasTo('admin.uno@ad50.cl')
            && $mail->hasTo('jefe@ad50.cl')
            && ! $mail->hasTo('ajena@empresa.cl')
            && ! $mail->hasTo('contacto.ad50.portal@gmail.com');
    });

    Mail::assertSentCount(2);
});

test('el aviso responde a quien escribió y lleva el mensaje', function () {
    Mail::fake();

    User::factory()->create(['role' => 'admin', 'email' => 'admin@ad50.cl']);
    $autor = postulanteParaAyuda();

    Livewire::actingAs($autor)
        ->test(Ayuda::class)
        ->set('motivo', 'servicios')
        ->set('mensaje', 'Me interesa saber si el plan Premium incluye publicaciones.')
        ->call('enviar');

    Mail::assertSent(MensajeContactoRecibido::class, function (MensajeContactoRecibido $mail) use ($autor) {
        // Responder desde el cliente de correo contesta a la persona, no a la casilla.
        return $mail->hasReplyTo($autor->email)
            && str_contains($mail->envelope()->subject, 'Consultas sobre los servicios')
            && str_contains($mail->envelope()->subject, $autor->name);
    });
});

test('el mensaje queda guardado aunque el correo falle', function () {
    // La bandeja es la fuente de verdad; el correo es solo el aviso. Un servidor caído no
    // puede hacer que la persona vea un error por algo que sí se guardó.
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP caído'));
    Log::spy();

    User::factory()->create(['role' => 'admin', 'email' => 'admin@ad50.cl']);

    Livewire::actingAs(postulanteParaAyuda())
        ->test(Ayuda::class)
        ->set('motivo', 'otras')
        ->set('mensaje', 'Tengo una duda que no aparece en las preguntas frecuentes.')
        ->call('enviar')
        ->assertHasNoErrors()
        ->assertSet('mensaje', '');

    expect(MensajeContacto::query()->where('motivo', 'otras')->exists())->toBeTrue();

    // El fallo no se traga en silencio: queda constancia para poder responder igual.
    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $texto): bool => str_contains($texto, 'No se pudo avisar por correo'))
        ->once();
});

test('sin cuentas de administración el mensaje se guarda igual', function () {
    Mail::fake();

    // Base sin admins: no hay a quién avisar, pero la bandeja conserva el mensaje.
    Livewire::actingAs(postulanteParaAyuda())
        ->test(Ayuda::class)
        ->set('motivo', 'servicios')
        ->set('mensaje', 'Quisiera cotizar el servicio para una empresa mediana.')
        ->call('enviar')
        ->assertHasNoErrors();

    Mail::assertNothingSent();
    expect(MensajeContacto::query()->where('motivo', 'servicios')->exists())->toBeTrue();
});
