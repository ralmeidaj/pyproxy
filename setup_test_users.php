<?php
// Atualiza BackofficeUser com senha conhecida e TOTP confirmado (para teste)
$bo = App\Models\BackofficeUser::first();
$bo->update([
    'password'            => bcrypt('Test@1234'),
    'totp_confirmed_at'   => now(),
]);
echo "BackofficeUser: email={$bo->email} password=Test@1234 totp=confirmed\n";

// Cria TenantUser para teste
$tenant = App\Models\Tenant::first();
$existing = App\Models\TenantUser::where('email', 'usuario@tenant.com.br')->first();
if ($existing) {
    $existing->update([
        'password'          => bcrypt('Test@1234'),
        'totp_confirmed_at' => now(),
        'status'            => 'active',
    ]);
    $tu = $existing;
} else {
    $tu = App\Models\TenantUser::create([
        'tenant_id'         => $tenant->id,
        'name'              => 'Usuario Teste',
        'email'             => 'usuario@tenant.com.br',
        'password'          => bcrypt('Test@1234'),
        'role'              => 'admin',
        'status'            => 'active',
        'totp_confirmed_at' => now(),
    ]);
}
echo "TenantUser: email={$tu->email} password=Test@1234 tenant_id={$tu->tenant_id} totp=confirmed\n";
echo "Tenant: {$tenant->name} (id={$tenant->id})\n";
