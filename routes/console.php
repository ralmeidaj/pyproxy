<?php

use App\Console\Commands\ReconcileBoletoCommand;
use App\Console\Commands\SendDueSoonNotificationsCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alertas de vencimento e expiração — 08:00 diariamente
Schedule::command(SendDueSoonNotificationsCommand::class)->dailyAt('08:00');

// Conciliação ativa noturna — 02:00 diariamente (RF-29)
Schedule::command(ReconcileBoletoCommand::class)->dailyAt('02:00')->withoutOverlapping();
