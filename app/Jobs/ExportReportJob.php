<?php

namespace App\Jobs;

use App\Models\Boleto;
use App\Models\ReportExport;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ExportReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly int $exportId) {}

    public function handle(): void
    {
        $export = ReportExport::findOrFail($this->exportId);

        if ($export->status === 'completed') {
            return; // idempotente
        }

        $export->update(['status' => 'processing']);

        try {
            $filters = $export->filters ?? [];
            $from    = $filters['from']   ?? now()->subMonth()->toDateString();
            $to      = $filters['to']     ?? now()->toDateString();
            $status  = $filters['status'] ?? null;
            $channel = $filters['channel'] ?? null;

            $tenant = $export->tenant_id ? Tenant::find($export->tenant_id) : null;

            $rows = Boleto::query()
                ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
                ->when($status,  fn ($q) => $q->where('status', $status))
                ->when($channel, fn ($q) => $q->where('paid_channel', $channel))
                ->whereBetween('created_at', [
                    Carbon::parse($from)->startOfDay(),
                    Carbon::parse($to)->endOfDay(),
                ])
                ->orderByDesc('created_at')
                ->get();

            $count   = $rows->count();
            $content = $export->format === 'csv'
                ? $this->toCsv($rows)
                : json_encode($rows->map(fn ($b) => $this->rowToArray($b)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $path = "exports/{$export->id}.{$export->format}";
            Storage::disk('s3')->put($path, $content);

            $url = Storage::disk('s3')->temporaryUrl($path, now()->addHours(24));

            $export->update([
                'status'       => 'completed',
                'row_count'    => $count,
                'file_path'    => $path,
                'download_url' => $url,
                'expires_at'   => now()->addHours(24),
            ]);
        } catch (\Throwable $e) {
            $export->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function toCsv($rows): string
    {
        $headers = [
            'id', 'external_ref', 'status', 'amount_cents', 'due_date',
            'payer_name', 'payer_document', 'payer_email',
            'paid_at', 'paid_amount_cents', 'paid_channel', 'created_at',
        ];

        $lines = [implode(',', $headers)];

        foreach ($rows as $r) {
            $lines[] = implode(',', [
                $r->id,
                '"' . str_replace('"', '""', $r->external_ref) . '"',
                $r->status->value,
                $r->amount_cents,
                $r->due_date?->toDateString(),
                '"' . str_replace('"', '""', $r->payer_name) . '"',
                $r->payer_document,
                $r->payer_email,
                $r->paid_at?->toIso8601String(),
                $r->paid_amount_cents,
                $r->paid_channel,
                $r->created_at?->toIso8601String(),
            ]);
        }

        return implode("\n", $lines);
    }

    private function rowToArray(Boleto $b): array
    {
        return [
            'id'               => $b->id,
            'external_ref'     => $b->external_ref,
            'status'           => $b->status->value,
            'amount_cents'     => $b->amount_cents,
            'due_date'         => $b->due_date?->toDateString(),
            'payer_name'       => $b->payer_name,
            'payer_document'   => $b->payer_document,
            'payer_email'      => $b->payer_email,
            'paid_at'          => $b->paid_at?->toIso8601String(),
            'paid_amount_cents' => $b->paid_amount_cents,
            'paid_channel'     => $b->paid_channel,
            'created_at'       => $b->created_at?->toIso8601String(),
        ];
    }
}
