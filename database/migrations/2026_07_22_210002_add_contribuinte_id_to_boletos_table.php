<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->foreignId('contribuinte_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Contribuinte::class);
            $table->dropColumn('contribuinte_id');
        });
    }
};
