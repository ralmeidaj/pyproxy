<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backoffice_users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });

        Schema::table('tenant_users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });

        // Usuários existentes ganham 90 dias de carência a partir de hoje
        // CURRENT_TIMESTAMP é suportado por PostgreSQL e SQLite
        DB::statement('UPDATE backoffice_users SET password_changed_at = CURRENT_TIMESTAMP WHERE password_changed_at IS NULL');
        DB::statement('UPDATE tenant_users SET password_changed_at = CURRENT_TIMESTAMP WHERE password IS NOT NULL AND password_changed_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('backoffice_users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });

        Schema::table('tenant_users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};
