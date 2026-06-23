<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->string('token_facilitador')->nullable()->after('bank_boleto_id')
                ->comment('Token PJBank para operações futuras (cancelar, consultar)');
        });
    }

    public function down(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->dropColumn('token_facilitador');
        });
    }
};
