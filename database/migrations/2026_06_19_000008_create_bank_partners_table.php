<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['fintech', 'banco', 'correspondente_bancario']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('features')->comment('split, dda, pix, cnab240, cnab400');
            $table->string('base_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_partners');
    }
};
