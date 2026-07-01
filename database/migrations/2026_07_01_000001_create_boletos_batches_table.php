<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $table = 'boletos_batches';

    public function up(): void
    {
        Schema::create('boletos_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('api_key_id')->constrained('api_keys')->cascadeOnDelete();
            $table->string('external_ref')->index();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('total_count')->default(0);
            $table->unsignedSmallInteger('processed_count')->default(0);
            $table->unsignedSmallInteger('success_count')->default(0);
            $table->unsignedSmallInteger('error_count')->default(0);
            $table->jsonb('items');
            $table->jsonb('results')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'external_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletos_batches');
    }
};
