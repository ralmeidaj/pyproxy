<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boleto_config_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('Rótulo do favorecido');
            $table->string('bank_partner_payee_id')->comment('ID do favorecido no parceiro bancário');
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('value', 10, 4)->comment('Percentual (ex: 15.5000) ou centavos (ex: 1000.0000)');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_configs');
    }
};
