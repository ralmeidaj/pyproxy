<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_digital_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->boolean('pixel_tracking')->default(true);    // passo 3 — abertura via pixel
            $table->boolean('cpf_confirmation')->default(true);  // passo 4 — confirmação por CPF
            $table->string('act_provider', 30)->default('bry'); // serpro|bry|soluti|globalsign
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_digital_configs');
    }
};
