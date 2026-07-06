<?php

use App\Http\Middleware\EnsureEmpresaActiva;
use App\Livewire\Admin\Empresas as AdminEmpresas;
use App\Livewire\Admin\Panel as AdminPanel;
use App\Livewire\Auth\Register;
use App\Livewire\Empresa\Activacion as EmpresaActivacion;
use App\Livewire\Empresa\Busquedas as EmpresaBusquedas;
use App\Livewire\Empresa\Candidato;
use App\Livewire\Empresa\NuevaBusqueda;
use App\Livewire\Empresa\Panel as EmpresaPanel;
use App\Livewire\Empresa\Resultados;
use App\Livewire\Landing;
use App\Livewire\Planes;
use App\Livewire\Postulante\Busquedas as PostulanteBusquedas;
use App\Livewire\Postulante\Ficha;
use App\Livewire\Postulante\Panel;
use App\Livewire\Postulante\Planes as PostulantePlanes;
use Illuminate\Support\Facades\Route;

Route::get('/', Landing::class)->name('home');
Route::get('/registro', Register::class)->name('registro');
Route::get('/planes', Planes::class)->name('planes');
Route::get('/planes/postulantes', PostulantePlanes::class)->name('planes.postulantes');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/postulante', Panel::class)->name('postulante.panel');
    Route::get('/postulante/ficha', Ficha::class)->name('postulante.ficha');
    Route::get('/postulante/busquedas', PostulanteBusquedas::class)->name('postulante.busquedas');

    Route::get('/empresa/activacion', EmpresaActivacion::class)->name('empresa.activacion');

    Route::middleware(EnsureEmpresaActiva::class)->group(function () {
        Route::get('/empresa', EmpresaPanel::class)->name('empresa.panel');
        Route::get('/empresa/busquedas', EmpresaBusquedas::class)->name('empresa.busquedas.index');
        Route::get('/empresa/busquedas/nueva', NuevaBusqueda::class)->name('empresa.busquedas.create');
        Route::get('/empresa/busquedas/{busqueda}/editar', NuevaBusqueda::class)->name('empresa.busquedas.edit');
        Route::get('/empresa/busquedas/{busqueda}', Resultados::class)->name('empresa.resultados');
        Route::get('/empresa/candidatos/{match}', Candidato::class)->name('empresa.candidatos.show');
    });

    Route::get('/admin', AdminPanel::class)->name('admin.panel');
    Route::get('/admin/empresas', AdminEmpresas::class)->name('admin.empresas');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
