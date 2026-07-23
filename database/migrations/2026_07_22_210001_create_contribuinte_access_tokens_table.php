<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribuinte_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('cpf_hash', 64)->index();
            $table->text('cpf_encrypted');
            $table->uuid('token')->unique()->index();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribuinte_access_tokens');
    }
};
