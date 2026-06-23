<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document', 14)->unique();         // CNPJ digits only
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['pending_approval', 'active', 'suspended', 'inactive'])
                  ->default('pending_approval');
            $table->enum('communication_model', ['email', 'email_whatsapp'])
                  ->default('email');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
