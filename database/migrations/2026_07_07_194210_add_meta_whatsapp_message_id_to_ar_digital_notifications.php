<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ar_digital_notifications', function (Blueprint $table) {
            // Armazena o wamid retornado pela Meta API para correlacionar com o webhook de entrega
            $table->string('meta_whatsapp_message_id', 100)->nullable()->after('destinatario_whatsapp');
            $table->index('meta_whatsapp_message_id', 'ar_notif_meta_wamid_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ar_digital_notifications', function (Blueprint $table) {
            $table->dropIndex('ar_notif_meta_wamid_idx');
            $table->dropColumn('meta_whatsapp_message_id');
        });
    }
};
