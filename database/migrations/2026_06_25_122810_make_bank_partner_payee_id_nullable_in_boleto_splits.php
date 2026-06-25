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
        Schema::table('boleto_splits', function (Blueprint $table): void {
            // PJBank usa payee_details em vez de ID pré-cadastrado no parceiro
            $table->string('bank_partner_payee_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('boleto_splits', function (Blueprint $table): void {
            $table->string('bank_partner_payee_id')->nullable(false)->change();
        });
    }
};
