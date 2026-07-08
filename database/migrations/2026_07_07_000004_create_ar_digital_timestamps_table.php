<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_digital_timestamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('ar_digital_events')
                ->cascadeOnDelete();

            // Dados do carimbo RFC 3161
            $table->string('hash_input', 64);          // SHA-256 dos dados carimbados
            $table->text('tsr_base64');                 // Time Stamp Response da ACT (base64)
            $table->string('act_provider', 30);         // serpro|bry|soluti|globalsign
            $table->timestamp('verificado_em')->nullable(); // quando o TSR foi verificado

            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('ar_digital_timestamps', function (Blueprint $table) {
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_digital_timestamps');
    }
};
