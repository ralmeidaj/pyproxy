<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_digital_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')
                ->constrained('ar_digital_notifications')
                ->cascadeOnDelete();

            // Tipo do evento
            // envio|entrega_provedor|abertura|confirmacao_cpf|bounce
            $table->string('tipo', 30);

            // Canal de comunicação
            $table->string('canal', 20); // email|whatsapp

            // Dados de rastreamento (preenchidos nos eventos de abertura/confirmação)
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('geolocation', 100)->nullable();

            // Dados SMTP (preenchidos nos eventos de entrega/bounce)
            $table->string('smtp_code', 10)->nullable();
            $table->text('smtp_response')->nullable();

            // Referência ao carimbo RFC 3161 deste evento (MinIO)
            $table->string('tsr_path', 500)->nullable();

            $table->timestamp('ocorrido_em')->useCurrent();
        });

        Schema::table('ar_digital_events', function (Blueprint $table) {
            $table->index(['notification_id', 'tipo']);
            $table->index('ocorrido_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_digital_events');
    }
};
