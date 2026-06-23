<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');                                // Label for identification
            $table->string('key_prefix', 12);                     // First chars shown after creation
            $table->string('key_hash', 64)->unique();             // SHA-256 of full key
            $table->json('scopes');                               // ['boleto:write','boleto:read',...]
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->unsignedInteger('daily_limit')->nullable();
            $table->unsignedInteger('monthly_limit')->nullable();
            $table->unsignedBigInteger('max_amount_cents')->nullable(); // per boleto
            $table->boolean('allow_batch')->default(true);
            $table->json('allowed_metadata_types')->nullable();   // e.g. ["IPTU","ISS"]
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('backoffice_users');
            $table->timestamps();

            $table->index(['key_hash', 'revoked_at']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
