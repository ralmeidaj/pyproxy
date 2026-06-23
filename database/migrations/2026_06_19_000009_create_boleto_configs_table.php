<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boleto_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_partner_id')->constrained();
            $table->string('name')->comment('Rótulo da configuração, ex: Produção PJBank');
            $table->boolean('is_default')->default(false);
            $table->text('credentials_encrypted')->comment('JSON com credenciais do parceiro, AES-256-GCM');
            $table->unsignedSmallInteger('prazo_vencimento_dias')->default(30);
            $table->decimal('multa_percentual', 5, 2)->default(0);
            $table->decimal('juros_percentual_mes', 5, 2)->default(0);
            $table->decimal('desconto_percentual', 5, 2)->nullable();
            $table->unsignedSmallInteger('desconto_antecedencia_dias')->nullable();
            $table->json('instrucoes')->nullable()->comment('Array com até 2 linhas de instrução');
            $table->string('webhook_url')->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boleto_configs');
    }
};
