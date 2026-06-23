<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insert-only — snapshot imutável dos splits aplicados ao boleto
        Schema::create('boleto_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleto_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('bank_partner_payee_id');
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('value', 10, 4);
            $table->unsignedBigInteger('amount_cents')->comment('Valor calculado em centavos');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boleto_splits');
    }
};
