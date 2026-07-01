<?php

namespace App\Console\Commands;

use App\Enums\BoletoStatus;
use App\Models\Boleto;
use Illuminate\Console\Command;

class ExpireBoletosCommand extends Command
{
    protected $signature = 'boletos:expire';

    protected $description = 'Marca como expirados os boletos pendentes com vencimento anterior a hoje (RF-28)';

    public function handle(): int
    {
        $expired = Boleto::where('status', BoletoStatus::Pending->value)
            ->whereDate('due_date', '<', today())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Nenhum boleto para expirar.');
            return Command::SUCCESS;
        }

        foreach ($expired as $boleto) {
            $boleto->update(['status' => BoletoStatus::Expired]);
        }

        $this->info("Boletos expirados: {$expired->count()}");
        return Command::SUCCESS;
    }
}
