<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_digital_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Destinatário
            $table->string('destinatario_email', 255)->nullable();
            $table->string('destinatario_whatsapp', 20)->nullable();
            $table->string('cpf_hash', 64)->nullable();       // SHA-256 do CPF (LGPD)

            // Integridade do documento
            $table->string('hash_documento', 64);             // SHA-256 do conteúdo do boleto
            $table->uuid('token')->unique();                  // token público de rastreamento

            // Status geral da notificação
            // enviado|entregue|lido|confirmado|bounce|falhou
            $table->string('status', 20)->default('enviado');

            // Laudo PDF gerado ao final da cadeia
            $table->string('laudo_path', 500)->nullable();    // caminho no MinIO

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('ar_digital_notifications', function (Blueprint $table) {
            $table->index(['boleto_id', 'created_at']);
            $table->index(['tenant_id', 'status']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_digital_notifications');
    }
};
