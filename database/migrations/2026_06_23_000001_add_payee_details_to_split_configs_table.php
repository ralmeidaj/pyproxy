<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('split_configs', function (Blueprint $table) {
            // Dados bancários do favorecido para repasse (formato PJBank e outros)
            $table->json('payee_details')->nullable()->after('bank_partner_payee_id')
                ->comment('nome, cnpj, banco_repasse, agencia_repasse, conta_repasse, porcentagem_encargos');

            // bank_partner_payee_id agora é opcional (alguns adapters usam ID simples, outros usam payee_details)
            $table->string('bank_partner_payee_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('split_configs', function (Blueprint $table) {
            $table->dropColumn('payee_details');
            $table->string('bank_partner_payee_id')->nullable(false)->change();
        });
    }
};
