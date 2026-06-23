<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            // Queries globais de relatório (sem tenant_id)
            $table->index(['status', 'created_at'], 'boletos_status_created_at_idx');
            $table->index(['status', 'paid_at'],    'boletos_status_paid_at_idx');
            $table->index(['status', 'due_date'],   'boletos_status_due_date_idx');

            // Queries de relatório por tenant (scoped)
            $table->index(['tenant_id', 'status', 'created_at'], 'boletos_tenant_status_created_at_idx');
            $table->index(['tenant_id', 'status', 'paid_at'],    'boletos_tenant_status_paid_at_idx');
            $table->index(['tenant_id', 'status', 'due_date'],   'boletos_tenant_status_due_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->dropIndex('boletos_status_created_at_idx');
            $table->dropIndex('boletos_status_paid_at_idx');
            $table->dropIndex('boletos_status_due_date_idx');
            $table->dropIndex('boletos_tenant_status_created_at_idx');
            $table->dropIndex('boletos_tenant_status_paid_at_idx');
            $table->dropIndex('boletos_tenant_status_due_date_idx');
        });
    }
};
