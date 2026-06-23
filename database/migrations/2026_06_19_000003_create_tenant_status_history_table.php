<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insert-only — sem soft-delete, sem update
        Schema::create('tenant_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('backoffice_user_id')->constrained();
            $table->enum('from_status', ['pending_approval', 'active', 'suspended', 'inactive'])->nullable();
            $table->enum('to_status', ['pending_approval', 'active', 'suspended', 'inactive']);
            $table->text('reason');
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_status_history');
    }
};
