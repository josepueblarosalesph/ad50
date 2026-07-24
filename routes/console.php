<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purga diaria de los procesos en papelera con más de 30 días.
Schedule::command('busquedas:purgar-eliminadas')->dailyAt('03:00');
