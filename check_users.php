<?php
// Backoffice users
$bo = App\Models\BackofficeUser::select('id','name','email','role','totp_confirmed_at')->get();
echo "=== BackofficeUsers (" . $bo->count() . ") ===\n";
foreach ($bo as $u) {
    echo "  id={$u->id} email={$u->email} role={$u->role} totp=" . ($u->totp_confirmed_at ? 'confirmed' : 'pending') . "\n";
}

// Tenant users
$tu = App\Models\TenantUser::select('id','name','email','role','tenant_id','totp_confirmed_at')->get();
echo "\n=== TenantUsers (" . $tu->count() . ") ===\n";
foreach ($tu as $u) {
    echo "  id={$u->id} email={$u->email} role={$u->role} tenant_id={$u->tenant_id} totp=" . ($u->totp_confirmed_at ? 'confirmed' : 'pending') . "\n";
}

// Tenants
$tenants = App\Models\Tenant::select('id','name','status')->get();
echo "\n=== Tenants (" . $tenants->count() . ") ===\n";
foreach ($tenants as $t) {
    echo "  id={$t->id} name={$t->name} status={$t->status}\n";
}
