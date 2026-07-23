<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('cpf_hash', 64);
            $table->string('phone_hash', 64);
            $table->string('consent_text_version', 20)->default('1.0');
            $table->timestamp('consented_at');
            $table->string('consent_ip', 45)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'cpf_hash']);
            $table->index('cpf_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_consents');
    }
};
