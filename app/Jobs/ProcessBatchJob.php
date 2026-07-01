<?php

namespace App\Jobs;

use App\DTOs\IssueBoletoData;
use App\Enums\BatchStatus;
use App\Models\BoletosBatch;
use App\Models\Tenant;
use App\Services\BoletoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 min — até 500 boletos

    public function __construct(private readonly int $batchId) {}

    public function handle(BoletoService $boletoService): void
    {
        $batch = BoletosBatch::findOrFail($this->batchId);

        if ($batch->status->isFinal()) {
            return; // job duplicado — idempotência
        }

        $batch->update([
            'status'     => BatchStatus::Processing,
            'started_at' => now(),
        ]);

        $tenant  = Tenant::findOrFail($batch->tenant_id);
        $items   = $batch->items;
        $results = [];

        foreach ($items as $item) {
            $pedidoNumero = $item['pedido_numero'];

            try {
                $data   = IssueBoletoData::fromArray($item);
                $boleto = $boletoService->issue($tenant, $data);

                $results[$pedidoNumero] = [
                    'status'        => 'success',
                    'boleto_id'     => $boleto->id,
                    'bank_boleto_id' => $boleto->bank_boleto_id,
                    'barcode'       => $boleto->barcode,
                    'digitable_line' => $boleto->digitable_line,
                    'pix_qr_code'   => $boleto->pix_qr_code,
                    'pdf_url'       => $boleto->pdf_url,
                    'amount_cents'  => $boleto->amount_cents,
                    'due_date'      => $boleto->due_date,
                ];

                $batch->increment('success_count');
            } catch (Throwable $e) {
                $results[$pedidoNumero] = [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ];

                $batch->increment('error_count');

                Log::warning('ProcessBatchJob: falha ao emitir boleto do lote', [
                    'batch_id'      => $batch->id,
                    'pedido_numero' => $pedidoNumero,
                    'error'         => $e->getMessage(),
                ]);
            }

            $batch->increment('processed_count');
        }

        $successCount = $batch->fresh()->success_count;
        $errorCount   = $batch->fresh()->error_count;
        $total        = count($items);

        $finalStatus = match(true) {
            $errorCount === 0           => BatchStatus::Completed,
            $successCount === 0         => BatchStatus::Failed,
            default                     => BatchStatus::Partial,
        };

        $batch->update([
            'status'      => $finalStatus,
            'results'     => $results,
            'finished_at' => now(),
        ]);

        Log::info('ProcessBatchJob: lote concluído', [
            'batch_id'      => $batch->id,
            'external_ref'  => $batch->external_ref,
            'total'         => $total,
            'success'       => $successCount,
            'errors'        => $errorCount,
            'status'        => $finalStatus->value,
        ]);
    }

    public function failed(Throwable $e): void
    {
        BoletosBatch::find($this->batchId)?->update([
            'status'      => BatchStatus::Failed,
            'finished_at' => now(),
        ]);

        Log::error('ProcessBatchJob: job falhou', [
            'batch_id' => $this->batchId,
            'error'    => $e->getMessage(),
        ]);
    }
}
