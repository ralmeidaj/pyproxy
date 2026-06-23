<?php
$service = app(App\Services\ReportService::class);
$tenant  = App\Models\Tenant::first();

echo "=== summary() global (sem tenant) ===\n";
$s = $service->summary(null, now()->subMonth()->toDateString(), now()->toDateString());
$keys = ['total_issued','total_paid','total_cancelled','total_expired','amount_issued_cents','amount_paid_cents','liquidation_rate','avg_ticket_cents'];
foreach ($keys as $k) {
    if (!array_key_exists($k, $s)) { echo "  FALTANDO: $k\n"; continue; }
    echo "  $k=" . (is_float($s[$k]) ? number_format($s[$k], 2) : $s[$k]) . "\n";
}

echo "\n=== summary() com tenant (id={$tenant->id}) ===\n";
$s2 = $service->summary($tenant, now()->subMonth()->toDateString(), now()->toDateString());
echo "  total_issued={$s2['total_issued']} liquidation_rate={$s2['liquidation_rate']}%\n";

echo "\n=== byChannel() ===\n";
$c = $service->byChannel(null, now()->subMonth()->toDateString(), now()->toDateString());
echo "  " . count($c) . " canais retornados\n";
$keys = ['channel','count','amount_cents'];
if (!empty($c)) {
    foreach ($keys as $k) {
        if (!array_key_exists($k, $c[0])) echo "  FALTANDO: $k\n";
    }
}

echo "\n=== delinquency() ===\n";
$d = $service->delinquency(null);
$keys = ['total_overdue','over_30_days','over_60_days','over_90_days','total_overdue_cents'];
foreach ($keys as $k) {
    if (!array_key_exists($k, $d)) { echo "  FALTANDO: $k\n"; continue; }
    echo "  $k={$d[$k]}\n";
}

echo "\n=== export() — cria ReportExport e despacha job ===\n";
$export = $service->export(
    tenant:          $tenant,
    filters:         ['from' => now()->subMonth()->toDateString(), 'to' => now()->toDateString()],
    format:          'csv',
    requestedById:   1,
    requestedByType: 'backoffice_user',
);
echo "  ReportExport id={$export->id} status={$export->status} format={$export->format}\n";
echo "  tenant_id={$export->tenant_id} requested_by={$export->requested_by_type}\n";

echo "\nOK — ReportService sem erros.\n";
