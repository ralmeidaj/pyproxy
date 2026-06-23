<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('boleto_config_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_partner_id')->constrained();
            $table->string('external_ref')->comment('Referência do tenant');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'expired'])->default('pending');
            $table->unsignedBigInteger('amount_cents');
            $table->date('due_date');

            // Dados do pagador
            $table->string('payer_name');
            $table->string('payer_document', 14)->comment('CPF ou CNPJ sem formatação');
            $table->string('payer_email')->nullable();
            $table->string('payer_phone', 20)->nullable();
            $table->json('payer_address')->nullable();

            // Dados retornados pelo parceiro bancário
            $table->string('bank_boleto_id')->nullable()->comment('ID do boleto no parceiro');
            $table->string('barcode', 60)->nullable()->comment('Código de barras CNAB');
            $table->string('digitable_line', 60)->nullable()->comment('Linha digitável');
            $table->text('pix_qr_code')->nullable();
            $table->string('pdf_url')->nullable();
            $table->boolean('dda_registered')->default(false);

            // Liquidação
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_amount_cents')->nullable();
            $table->string('paid_channel')->nullable()->comment('barcode, pix, dda');

            // Cancelamento
            $table->timestamp('cancelled_at')->nullable();

            // Snapshots imutáveis (RN-06)
            $table->json('config_snapshot')->comment('Snapshot da BoletoConfig no momento da emissão');
            $table->json('splits_snapshot')->comment('Snapshot dos splits aplicados');

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'external_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletos');
    }
};
