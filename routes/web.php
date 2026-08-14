<?php

use App\Http\Controllers\FlowController;
use App\Http\Middleware\EnsureEmpresaActiva;
use App\Http\Middleware\EnsurePostulanteOnboardingComplete;
use App\Livewire\Admin\Catalogos as AdminCatalogos;
use App\Livewire\Admin\Empresas as AdminEmpresas;
use App\Livewire\Admin\Mensajes as AdminMensajes;
use App\Livewire\Admin\Panel as AdminPanel;
use App\Livewire\Admin\Planes as AdminPlanes;
use App\Livewire\Admin\Postulantes as AdminPostulantes;
use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Livewire\Auth\Register;
use App\Livewire\Ayuda;
use App\Livewire\Empresa\Activacion as EmpresaActivacion;
use App\Livewire\Empresa\Busquedas as EmpresaBusquedas;
use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\DetallePublicacion;
use App\Livewire\Empresa\Equipo as EmpresaEquipo;
use App\Livewire\Empresa\Favoritos as EmpresaFavoritos;
use App\Livewire\Empresa\NuevaBusqueda;
use App\Livewire\Empresa\NuevaPublicacion;
use App\Livewire\Empresa\Panel as EmpresaPanel;
use App\Livewire\Empresa\Planes as EmpresaPlanes;
use App\Livewire\Empresa\Postulaciones as EmpresaPostulaciones;
use App\Livewire\Empresa\Publicaciones as EmpresaPublicaciones;
use App\Livewire\Empresa\Resultados;
use App\Livewire\Landing;
use App\Livewire\Planes;
use App\Livewire\Postulante\Busquedas as PostulanteBusquedas;
use App\Livewire\Postulante\DetallePublicacion as PostulanteDetallePublicacion;
use App\Livewire\Postulante\Ficha;
use App\Livewire\Postulante\Postulaciones as PostulantePostulaciones;
use App\Livewire\QuienesSomos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', Landing::class)->name('home');
Route::get('/registro', Register::class)->name('registro');
Route::get('/planes', Planes::class)->name('planes');
Route::get('/quienes-somos', QuienesSomos::class)->name('quienes-somos');

// Callbacks de Flow (server-to-server y retorno del navegador). Sin auth ni CSRF.
Route::post('/pagos/flow/confirmar', [FlowController::class, 'confirmar'])->name('pagos.flow.confirmar');
Route::match(['get', 'post'], '/pagos/flow/retorno', [FlowController::class, 'retorno'])->name('pagos.flow.retorno');

Route::middleware(['auth', 'verified'])->group(function () {
    // Ayuda va fuera de los grupos por rol: la usan postulantes, empresas y admin, y
    // queda antes del gating de onboarding y de activación a propósito, porque quien
    // está atascado en esos pasos es justamente quien necesita escribirnos.
    Route::get('/ayuda', Ayuda::class)->name('ayuda');

    Route::get('/postulante/ficha', Ficha::class)->name('postulante.ficha');
    Route::middleware(EnsurePostulanteOnboardingComplete::class)->group(function () {
        // El postulante ya no tiene panel propio: entra a Oportunidades. La URL antigua
        // se conserva redirigiendo, para no romper enlaces guardados.
        Route::redirect('/postulante', '/postulante/busquedas');
        Route::get('/postulante/busquedas', PostulanteBusquedas::class)->name('postulante.busquedas');
        Route::get('/postulante/postulaciones', PostulantePostulaciones::class)->name('postulante.postulaciones');
        Route::get('/postulante/oportunidades/{publicacion}', PostulanteDetallePublicacion::class)->name('postulante.publicaciones.show');
    });

    // Pasos del onboarding de empresa: pagar y luego completar los datos.
    Route::get('/empresa/activacion', EmpresaActivacion::class)->name('empresa.activacion');
    Route::get('/empresa/planes', EmpresaPlanes::class)->name('empresa.planes');

    Route::middleware(EnsureEmpresaActiva::class)->group(function () {
        Route::get('/empresa', EmpresaPanel::class)->name('empresa.panel');
        Route::get('/empresa/equipo', EmpresaEquipo::class)->name('empresa.equipo');
        Route::get('/empresa/favoritos', EmpresaFavoritos::class)->name('empresa.favoritos');
        Route::get('/empresa/busquedas', EmpresaBusquedas::class)->name('empresa.busquedas.index');
        Route::get('/empresa/busquedas/nueva', NuevaBusqueda::class)->name('empresa.busquedas.create');
        Route::get('/empresa/busquedas/{busqueda}/editar', NuevaBusqueda::class)->name('empresa.busquedas.edit');
        Route::get('/empresa/busquedas/{busqueda}', Resultados::class)->name('empresa.resultados');
        Route::get('/empresa/candidatos/{match}', Candidato::class)->name('empresa.candidatos.show');
        Route::get('/empresa/publicaciones', EmpresaPublicaciones::class)->name('empresa.publicaciones.index');
        Route::get('/empresa/publicaciones/nueva', NuevaPublicacion::class)->name('empresa.publicaciones.create');
        Route::get('/empresa/publicaciones/{publicacion}/editar', NuevaPublicacion::class)->name('empresa.publicaciones.edit');
        Route::get('/empresa/publicaciones/{publicacion}/postulaciones', EmpresaPostulaciones::class)->name('empresa.publicaciones.postulaciones');
        Route::get('/empresa/publicaciones/{publicacion}', DetallePublicacion::class)->name('empresa.publicaciones.show');
    });

    // El gating de administración lo hace cada componente en su mount() (esAdmin() /
    // esSuperadmin()), igual que el resto del panel; Usuarios es la única exclusiva
    // del superadministrador.
    Route::get('/admin', AdminPanel::class)->name('admin.panel');
    Route::get('/admin/empresas', AdminEmpresas::class)->name('admin.empresas');
    Route::get('/admin/postulantes', AdminPostulantes::class)->name('admin.postulantes');
    Route::get('/admin/planes', AdminPlanes::class)->name('admin.planes');
    Route::get('/admin/catalogos', AdminCatalogos::class)->name('admin.catalogos');
    Route::get('/admin/mensajes', AdminMensajes::class)->name('admin.mensajes');
    Route::get('/admin/usuarios', AdminUsuarios::class)->name('admin.usuarios');
});

Route::get('dashboard', function (Request $request): RedirectResponse {
    return redirect()->route($request->user()->dashboardRouteName());
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
