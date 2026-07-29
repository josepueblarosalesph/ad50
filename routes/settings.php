<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    // Sin `password.confirm`: se entra directo al formulario de cambio de contraseña
    // (actual + nueva + confirmación) en vez de pasar por la pantalla intermedia que
    // vuelve a pedir la clave. Las acciones de 2FA de Fortify conservan su propia
    // confirmación (config/fortify.php, 'confirmPassword' => true).
    Route::livewire('settings/security', 'pages::settings.security')->name('security.edit');
});
