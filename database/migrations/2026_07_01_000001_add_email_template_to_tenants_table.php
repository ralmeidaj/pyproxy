<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('email_entity_name')->nullable()->after('communication_model');
            $table->string('email_logo_url')->nullable()->after('email_entity_name');
            $table->text('email_custom_text')->nullable()->after('email_logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['email_entity_name', 'email_logo_url', 'email_custom_text']);
        });
    }
};
