<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Support\SystemBackupService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Genera un respaldo privado cada noche y conserva los ultimos catorce.
 * Se conecta con SystemBackupService y requiere que el programador de Laravel este activo.
 */
Schedule::call(function () {
    $servicio = app(SystemBackupService::class);
    $servicio->create('automatico');
    $servicio->prune(14);
})->dailyAt('02:00')->name('movilphone-respaldo-diario')->withoutOverlapping();
