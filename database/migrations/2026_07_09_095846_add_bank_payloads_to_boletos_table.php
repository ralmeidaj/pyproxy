<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boletos', function (Blueprint $table): void {
            $table->jsonb('bank_request_payload')->nullable()->after('splits_snapshot');
            $table->jsonb('bank_response_payload')->nullable()->after('bank_request_payload');
        });
    }

    public function down(): void
    {
        Schema::table('boletos', function (Blueprint $table): void {
            $table->dropColumn(['bank_request_payload', 'bank_response_payload']);
        });
    }
};