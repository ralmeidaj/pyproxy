<?php

namespace App\Console\Commands;

use App\Enums\BoletoStatus;
use App\Enums\NotificationEvent;
use App\Models\Boleto;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDueSoonNotificationsCommand extends Command
{
    protected $signature   = 'boletos:notify-due';
    protected $description = 'Envia alertas de vencimento (D-2) e marca como expirado + notifica vencidos (D+1)';

    public function __construct(private readonly NotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // D-2: vence em 2 dias
        $dueSoon = Boleto::where('status', BoletoStatus::Pending->value)
            ->whereDate('due_date', now()->addDays(2)->toDateString())
            ->get();

        foreach ($dueSoon as $boleto) {
            $this->notifications->notify($boleto, NotificationEvent::DueSoon);
        }

        $this->info("D-2: {$dueSoon->count()} notificações de vencimento próximo enviadas para fila.");

        // Vencidos: past due_date e ainda pending → marcar expired + notificar
        $expired = Boleto::where('status', BoletoStatus::Pending->value)
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($expired as $boleto) {
            // Atualiza silencioso (sem disparar o observer de status paid/cancelled)
            $boleto->updateQuietly(['status' => BoletoStatus::Expired]);
            $this->notifications->notify($boleto, NotificationEvent::Overdue);
        }

        $this->info("Vencidos: {$expired->count()} boletos marcados como expirados e notificados.");

        return Command::SUCCESS;
    }
}
