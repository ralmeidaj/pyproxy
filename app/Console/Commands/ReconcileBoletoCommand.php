<?php

namespace App\Console\Commands;

use App\Services\ReconciliationService;
use Illuminate\Console\Command;

class ReconcileBoletoCommand extends Command
{
    protected $signature = 'boletos:reconcile
        {--window=30 : Janela em dias para selecionar boletos vencidos pendentes}
        {--batch=50  : Número de boletos por lote de consulta ao parceiro}';

    protected $description = 'Concilia boletos pendentes consultando proativamente o parceiro bancário (RF-29)';

    public function __construct(private readonly ReconciliationService $reconciliation)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $window = (int) $this->option('window');
        $batch  = (int) $this->option('batch');

        $this->info("Iniciando conciliação: janela={$window}d, lote={$batch}");

        $stats = $this->reconciliation->run($window, $batch);

        $this->table(
            ['Consultados', 'Pagos', 'Outros status', 'Erros'],
            [[$stats['consulted'], $stats['paid'], $stats['other'], $stats['errors']]],
        );

        if ($stats['errors'] > 0) {
            $this->warn("{$stats['errors']} erro(s) durante a conciliação — verifique os logs.");
        }

        return Command::SUCCESS;
    }
}
