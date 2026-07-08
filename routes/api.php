<?php

use App\Http\Controllers\Api\MetaWhatsAppWebhookController;
use App\Http\Controllers\Api\SmtpDsnController;
use App\Http\Controllers\Api\V1\BatchController;
use App\Http\Controllers\Api\V1\BoletoController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

// API pública v1 — autenticada via API Key (RF-AC-14)
Route::prefix('v1')->name('api.v1.')->middleware(['api.key'])->group(function () {

    // Lote de boletos (RF-16, RF-17) — declarado antes de {boleto} por boas práticas de rota
    Route::post('boletos/batch', [BatchController::class, 'store'])->name('boletos.batch.store');
    Route::get('boletos/batch/{batch}', [BatchController::class, 'show'])->name('boletos.batch.show');

    // Boletos individuais (RF-01 a RF-08)
    Route::post('boletos', [BoletoController::class, 'store'])->name('boletos.store');
    Route::get('boletos/{boleto}', [BoletoController::class, 'show'])->name('boletos.show');
    Route::delete('boletos/{boleto}', [BoletoController::class, 'destroy'])->name('boletos.destroy');
});

// Webhooks recebidos dos parceiros bancários (sem autenticação API Key — verificação por HMAC no controller/middleware)
Route::prefix('webhooks')->name('api.webhooks.')->group(function () {
    Route::post('pjbank', [WebhookController::class, 'pjbank'])
        ->middleware('webhook.hmac')
        ->name('pjbank');

    // DSN SMTP — notificações de entrega/bounce do provedor de e-mail
    Route::post('smtp-dsn', [SmtpDsnController::class, 'handle'])->name('smtp-dsn');

    // Meta WhatsApp — verificação e eventos de entrega/leitura
    Route::get('meta-whatsapp',  [MetaWhatsAppWebhookController::class, 'verify'])->name('meta-whatsapp.verify');
    Route::post('meta-whatsapp', [MetaWhatsAppWebhookController::class, 'handle'])->name('meta-whatsapp.handle');
});
