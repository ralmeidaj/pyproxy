<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anonymization_requests', function (Blueprint $table) {
            $table->id();
            $table->string('cpf_hash', 64)->index();
            $table->json('boleto_ids');
            $table->string('status', 20)->default('pending');
            $table->string('payer_email_masked', 255)->nullable();
            $table->integer('boleto_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->string('processed_by_label', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymization_requests');
    }
};
