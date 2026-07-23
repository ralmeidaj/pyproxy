<?php

use App\Console\Commands\ExpireBoletosCommand;
use App\Console\Commands\ReconcileBoletoCommand;
use App\Console\Commands\RetryWebhooksCommand;
use App\Console\Commands\RunDataRetentionCommand;
use App\Console\Commands\SendDueSoonNotificationsCommand;
use App\Jobs\NotifyExpiringApiKeysJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expiração de boletos vencidos — meia-noite diariamente (RF-28)
Schedule::command(ExpireBoletosCommand::class)->dailyAt('00:00')->withoutOverlapping();

// Alertas de vencimento próximo — 08:00 diariamente
Schedule::command(SendDueSoonNotificationsCommand::class)->dailyAt('08:00');

// Conciliação ativa noturna — 02:00 diariamente (RF-29)
Schedule::command(ReconcileBoletoCommand::class)->dailyAt('02:00')->withoutOverlapping();

// Reprocessa webhooks falhados com next_attempt_at vencido — a cada minuto (RF-27)
Schedule::command(RetryWebhooksCommand::class)->everyMinute()->withoutOverlapping();

// Notifica API Keys expirando em 15 e 7 dias — 09:00 diariamente
Schedule::job(NotifyExpiringApiKeysJob::class)->dailyAt('09:00');

// Expurgo e anonimização LGPD — domingos às 03:00 semanalmente
Schedule::command(RunDataRetentionCommand::class)->weeklyOn(0, '03:00')->withoutOverlapping();
