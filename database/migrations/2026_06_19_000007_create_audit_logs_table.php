<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insert-only — sem FK externas para preservar histórico após soft-delete
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');                  // 'backoffice_user'|'tenant_user'|'api_key'|'system'
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_label');                 // nome/email snapshot
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('action');                      // 'tenant.approve','api_key.revoke', etc.
            $table->string('resource_type');               // 'Tenant','ApiKey','Boleto', etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('payload')->nullable();           // dados relevantes da operação
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Índices de consulta (RF-41)
        \Illuminate\Support\Facades\DB::statement(
            'CREATE INDEX audit_logs_tenant_created ON audit_logs (tenant_id, created_at DESC)'
        );
        \Illuminate\Support\Facades\DB::statement(
            'CREATE INDEX audit_logs_actor ON audit_logs (actor_type, actor_id)'
        );
        \Illuminate\Support\Facades\DB::statement(
            'CREATE INDEX audit_logs_action ON audit_logs (action)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
