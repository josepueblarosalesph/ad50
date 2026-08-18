<?php

use App\Livewire\Admin\Mensajes as AdminMensajes;
use App\Livewire\Admin\Panel as AdminPanel;
use App\Models\Empresa;
use App\Models\MensajeContacto;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * La campana de avisos de la barra superior y la limpieza del panel de administración.
 */
function mensajeDeContacto(string $estado = 'nuevo', string $nombre = 'Marta Rojas'): MensajeContacto
{
    return MensajeContacto::query()->create([
        'nombre' => $nombre,
        'email' => Str::slug($nombre).'@correo.cl',
        'motivo' => 'soporte',
        'mensaje' => 'No puedo entrar a mi cuenta.',
        'estado' => $estado,
    ]);
}

test('the bell counts the unread messages for an admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    mensajeDeContacto('nuevo', 'Marta Rojas');
    mensajeDeContacto('nuevo', 'Carlos Vega');
    mensajeDeContacto('leido', 'Paula Diaz');       // ya visto: no es una novedad
    mensajeDeContacto('respondido', 'Ana Silva');   // cerrado

    $this->actingAs($admin)
        ->get(route('admin.panel'))
        ->assertOk()
        ->assertSee('Avisos: 2 mensajes sin leer')
        // El desplegable lista quién escribió.
        ->assertSee('Marta Rojas')
        ->assertSee('Carlos Vega')
        // Pendientes = sin leer + leídos sin responder.
        ->assertSee('3 pendientes');
});

test('with no unread messages the bell shows no badge', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    mensajeDeContacto('respondido');

    $this->actingAs($admin)
        ->get(route('admin.panel'))
        ->assertOk()
        ->assertSee('Avisos: no hay mensajes sin leer')
        ->assertSee('No hay mensajes sin leer');
});

test('a single unread message is announced in singular', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    mensajeDeContacto('nuevo');

    $this->actingAs($admin)
        ->get(route('admin.panel'))
        ->assertSee('Avisos: 1 mensaje sin leer');
});

test('the superadmin also gets the bell', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    mensajeDeContacto('nuevo');

    $this->actingAs($superadmin)
        ->get(route('admin.usuarios'))
        ->assertOk()
        ->assertSee('Avisos: 1 mensaje sin leer');
});

test('the bell never reaches a postulante or a company', function () {
    mensajeDeContacto('nuevo');

    $postulante = User::factory()->create(['role' => 'postulante']);
    Postulante::query()->create(['user_id' => $postulante->id, 'onboarding_completado' => true]);

    $this->actingAs($postulante)
        ->get(route('postulante.busquedas'))
        ->assertOk()
        ->assertDontSee('Avisos:')
        ->assertDontSee('Ver la bandeja');

    $empresaUser = User::factory()->create(['role' => 'empresa']);
    Empresa::query()->create([
        'user_id' => $empresaUser->id,
        'razon_social' => 'Retail Andes SpA',
        'estado_activacion' => 'activa',
        'datos_enviados_at' => now(),
    ]);

    $this->actingAs($empresaUser)
        ->get(route('empresa.planes'))
        ->assertOk()
        ->assertDontSee('Avisos:');
});

test('reading a message stops it from being announced as new', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $mensaje = mensajeDeContacto('nuevo');

    // Abrirlo en la bandeja lo pasa a leído; el estado es de la administración entera.
    Livewire::actingAs($admin)
        ->test(AdminMensajes::class)
        ->call('abrir', $mensaje->id);

    $this->actingAs($admin)
        ->get(route('admin.panel'))
        ->assertSee('Avisos: no hay mensajes sin leer')
        // Pero sigue contando como trabajo pendiente.
        ->assertSee('1 pendiente');
});

// --- Limpieza del panel -------------------------------------------------------

test('the admin panel no longer carries the duplicated sidebar menu', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $respuesta = $this->actingAs($admin)->get(route('admin.panel'))->assertOk();

    // Las dos secciones que solo existían en aquel menú y no llevaban a ninguna parte.
    $respuesta->assertDontSee('Seguridad y auditoría')
        ->assertDontSee('Suscripciones');

    // Y no queda ningún enlace muerto en la pantalla.
    $respuesta->assertDontSeeHtml('href="#"');
});

test('the panel shortcuts point to the real screens', function () {
    // Con el superadministrador porque el atajo a catálogos es exclusivo suyo; que al
    // admin común no se le ofrezca está cubierto en SuperadminPanelTest.
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($superadmin)
        ->get(route('admin.panel'))
        ->assertOk()
        ->assertSeeHtml('href="'.route('admin.empresas').'"')
        ->assertSeeHtml('href="'.route('admin.catalogos').'"')
        ->assertSeeHtml('href="'.route('admin.mensajes').'"');
});

test('the panel card reports how many messages are waiting', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.panel'))
        ->assertSee('No hay mensajes pendientes de respuesta.');

    mensajeDeContacto('nuevo');
    mensajeDeContacto('leido', 'Carlos Vega');

    Livewire::actingAs($admin)
        ->test(AdminPanel::class)
        ->assertSee('Hay 2 mensajes sin responder.');
});
