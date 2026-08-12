<?php

use App\Livewire\Admin\Mensajes as AdminMensajes;
use App\Livewire\Ayuda;
use App\Models\Empresa;
use App\Models\MensajeContacto;
use App\Models\Postulante;
use App\Models\User;
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
