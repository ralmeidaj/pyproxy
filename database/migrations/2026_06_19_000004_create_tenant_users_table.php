<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();           // null until invite accepted
            $table->enum('role', ['admin', 'operator', 'viewer'])->default('operator');
            $table->boolean('active')->default(false);
            $table->string('invite_token', 64)->nullable()->unique(); // SHA-256 hex
            $table->timestamp('invite_expires_at')->nullable();
            $table->string('totp_secret')->nullable();        // AES-256-GCM encrypted
            $table->boolean('totp_enabled')->default(false);
            $table->timestamp('totp_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_users');
    }
};
